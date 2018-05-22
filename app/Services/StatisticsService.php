<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Log\ItemLog;
use App\Models\Log\RoleLogin;
use App\Models\Log\RoleOnline;
use App\Models\TopUpPlayer;
use Carbon\Carbon;
use DB;

class StatisticsService
{
    // 耗卡(item_log) 增加是1，消耗是2
    const CARD_CONSUMED = 2;
    // 房卡
    const CARD_TYPE = 1;

    protected $player;
    protected $roleOnline;
    protected $itemLog;
    protected $topUpPlayer;

    public function __construct(TopUpPlayer $topUpPlayer, Player $player, RoleOnline $roleOnline, ItemLog $itemLog)
    {
        $this->topUpPlayer = $topUpPlayer;
        $this->player = $player;
        $this->roleOnline = $roleOnline;
        $this->itemLog = $itemLog;
    }

    /**
     * 全部玩家
     * @return mixed|int
     */
    public function getTotalPlayersAmount()
    {
        return $this->player->count();
    }

    /**
     * 在线玩家
     * @return mixed|int
     */
    public function getOnlinePlayersAmount()
    {
        return $this->player->where('online', 1)->count();
    }

    /**
     * 游戏中玩家
     * @return mixed|int
     */
    public function getInGamePlayersAmount()
    {
        return $this->player->where('on_game', 1)->count();
    }

    /**
     * 根据日期获取当月有过  [ 充卡记录的总玩家数, 充卡的总量 ]，默认查本月
     *
     * @param $date '格式2017-01'
     * @return array [$playersCount, $boughtCount]
     */
    public function getMonthlyCardBought($date = 'today')
    {
        $date = Carbon::parse($date)->format('Y-m');
        list($year, $month) = explode('-', $date);
        $result = $this->topUpPlayer
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('type', self::CARD_TYPE)
            ->get();

        $playersCount = $result->groupBy('player')->count();
        $boughtSum = $result->sum('amount');

        return [$playersCount, $boughtSum];
    }

    /**
     * 转为时间戳 对比
     * @param $date
     * @return array
     */
    public function transTimestamp($date)
    {
        // 2018-05-18 00:00:00
        $startTime = Carbon::parse($date)->format('Y-m-d');
        // 2018-05-19 00:00:00
        $endTime = Carbon::parse($date)->addDays(1)->timestamp;
        $startTime = strtotime($startTime);

        return [$startTime, $endTime];
    }

    /**
     * 日均在线
     * @param $date
     * @return float|int
     */
    public function getAverageOnlinePlayersCount($date)
    {
        $between = $this->transTimestamp($date);
        $result = $this->roleOnline->select(DB::raw('avg(num) as num_avg'))->whereBetween('date_time', $between)->first();

        return $result['num_avg'] ? ceil($result['num_avg']) : 0;
    }

    /**
     * 日高峰
     * @param $date
     * @return int
     */
    public function getPeakOnlinePlayersAmount($date)
    {
        $between = $this->transTimestamp($date);
        $result = $this->roleOnline->select(DB::raw('max(num) as num_max'))->whereBetween('date_time', $between)->first();

        return $result['num_max'] ?: 0;
    }

    /**
     * 活跃玩家
     * @param $date
     * @return int
     */
    public function getActivePlayersAmount($date)
    {
        $between = $this->transTimestamp($date);

        return RoleLogin::query()->select('rid')->whereBetween('login_time', $between)->groupBy('rid')->get()->count();
    }

    /**
     * 新增人数
     * @param $date
     * @return int
     */
    public function getIncrementalPlayersAmount($date)
    {
        $between = $this->transTimestamp($date);

        return $this->player->whereBetween('create_time', $between)->count();
    }

    /**
     * 耗卡总数
     * type : 增加是1，消耗是2
     * @param $date
     * @return mixed
     */
    public function getCardConsumedSum($date)
    {
        $between = $this->transTimestamp($date);

        return $this->itemLog->where('time', '<', $between[1])->where('type', self::CARD_CONSUMED)->sum('item_num');
    }

    /**
     * 购卡总数
     * @param $date
     * @return mixed
     */
    public function getCardBoughtSum($date)
    {
        return $this->topUpPlayer->whereDate('created_at', '<=', $date)->sum('amount');
    }

    /**
     * 根据日期获取当日购卡数据
     * @param $date
     * @return string '280|13|22 - 当日玩家购卡总数|当日有过购卡记录的玩家总数|平均购卡数(向上取整的比值)
     */
    public function getCardBoughtData($date)
    {
        $cards = $this->topUpPlayer->whereDate('created_at', $date)->where('type', self::CARD_TYPE)->get();

        //当日玩家购卡总数
        $cardBoughtAmount = $cards->sum('amount');

        //当日有过购卡记录的玩家总数
        $cardBoughtPlayersAmount = $cards->groupBy('player')->count();

        if ($cardBoughtPlayersAmount === 0) {
            return "{$cardBoughtAmount}|{$cardBoughtPlayersAmount}|0";
        }

        return "{$cardBoughtAmount}|{$cardBoughtPlayersAmount}|" .
            ceil($cardBoughtAmount / $cardBoughtPlayersAmount);
    }

    /**
     * 根据日期获取当日耗卡数据
     * @param $date
     * @return string '280|13|22 - 当日玩家耗卡总数|当日有过耗卡记录的玩家总数|平均耗卡数(向上取整的比值)
     */
    public function getCardConsumedData($date)
    {
        $between = $this->transTimestamp($date);
        $cards = $this->itemLog->where('type', self::CARD_CONSUMED)->whereBetween('time', $between)->get();

        //当日玩家耗卡总数
        $cardConsumed = $cards->sum('item_num');

        //当日有过耗卡记录的玩家总数
        $cardConsumedPlayerAmount = $cards->groupBy('rid')->count();

        if ($cardConsumedPlayerAmount === 0) {
            return "{$cardConsumed}|{$cardConsumedPlayerAmount}|0";
        }

        return "{$cardConsumed}|{$cardConsumedPlayerAmount}|" .
            ceil($cardConsumed / $cardConsumedPlayerAmount);

    }

    /**
     * 留存率 = 新增用户中登录用户数/新增用户数*100%（一般统计周期为天）
     * @param $date
     * @param $days
     * @return string '2|4|50.00 - 留存玩家数|创建日新增玩家数|百分比(保留两位小数)
     */
    public function getRemainedData($date, $days)
    {
        $between = $this->transRemainedDate($date, $days);
        // 新增用户数
        $newPlayer = $this->player->select('rid', 'create_time')->whereBetween('create_time', $between)->get();

        $countNewPlayer = $newPlayer->count();
        if ($countNewPlayer === 0) {
            return "0|0|0.00";
        }

        // 新增用户中登录用户数
        $loginNewPlayer = RoleLogin::query()
            ->whereIn('rid', $newPlayer->pluck('rid'))
            ->whereBetween('login_time', $between)
            ->groupBy('rid')
            ->count();

        $remainedRate = sprintf('%.2f', $loginNewPlayer / $countNewPlayer * 100);

        return "{$loginNewPlayer}|{$countNewPlayer}|$remainedRate";
    }

    /**
     * 转换留存时间
     * 2018-05-18, 1 => 2018-05-17 ~ < 2018-05-19
     * @param $date
     * @param $days
     * @return array
     */
    public function transRemainedDate($date, $days)
    {
        // 2018-05-18, 1 => 2018-05-17 ~ < 2018-05-19
        $endTime = Carbon::parse($date)->addDays(1)->format('Y-m-d');

        $startTime = Carbon::parse($date)->subDays($days)->timestamp;
        $endTime = strtotime($endTime);

        return [$startTime, $endTime];
    }
}