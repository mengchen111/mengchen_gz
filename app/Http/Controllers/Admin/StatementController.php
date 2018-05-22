<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 9/11/17
 * Time: 15:59
 */

namespace App\Http\Controllers\Admin;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\Group;
use App\Models\GroupIdMap;
use App\Models\OperationLogs;
use App\Models\StatementDaily;
use App\Models\TopUpAdmin;
use App\Models\User;
use App\Services\Paginator;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Excel;

class StatementController extends Controller
{
    use GroupIdMap;
    protected $cardTypeId = 1;
    protected $coinTypeId = 2;
    protected $cardPurchasedKey = 'card_purchased';     //获取汇总数据时使用的key(代理商购买的)
    protected $coinPurchasedKey = 'coin_purchased';
    protected $cardConsumedKey = 'card_consumed';       //给玩家充值消耗的
    protected $coinConsumedKey = 'coin_consumed';

    protected $per_page = 15;   //每页数据
    protected $page = 1;        //当前页

    protected $data = [
        'average_online_players' => 0,      //日均在线
        'peak_online_players' => 0,         //日高峰
//        'peak_in_game_players' => 0,        //当日最高处于游戏中的玩家数量
        'active_players' => 0,              //当日活跃玩家
        'incremental_players' => 0,         //新增玩家数
        'one_day_remained' => '0|0|0.00',   //次日留存, 留存玩家数|创建日新增玩家数|百分比(保留两位小数)
        'one_week_remained' => '0|0|0.00',  //7日留存
        'two_weeks_remained' => '0|0|0.00', //14日留存
        'one_month_remained' => '0|0|0.00', //30日留存
        'card_consumed_data' => '0|0|0',    //当日耗卡数|当日有过耗卡记录的玩家总数|平均耗卡数(向上取整的比值)
        'card_bought_data' => '0|0|0',      //当日玩家购卡总数|当日有过购卡记录的玩家总数|平均购卡数(向上取整的比值)
        'card_consumed_sum' => 0,           //截止当日玩家耗卡总数
        'card_bought_sum' => 0,             //截止当日给玩家充卡总数
        'monthly_card_bought_players' => 0, //当月累计充卡玩家数
        'monthly_card_bought_sum' => 0,     //当月累计给玩家充卡总数
    ];

    public function __construct(Request $request)
    {
        $this->per_page = $request->per_page ?: $this->per_page;
        $this->page = $request->page ?: $this->page;
    }

    /**
     * 数据总览
     * @param AdminRequest $request
     * @return array
     * @throws CustomException
     */
    public function index(AdminRequest $request)
    {
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
        ]);
        $date = $request->input('date');
        $statisticsService = app(StatisticsService::class);
        //月数据
        list($playersCount, $boughtSum) = $statisticsService->getMonthlyCardBought($date);
        $this->data['monthly_card_bought_players'] = $playersCount;
        $this->data['monthly_card_bought_sum'] = $boughtSum;

        //如果时间为今天，查询实时数据
        if (Carbon::parse($date)->isToday()) {
            $this->data['average_online_players'] = $statisticsService->getAverageOnlinePlayersCount($date);
            $this->data['peak_online_players'] = $statisticsService->getPeakOnlinePlayersAmount($date);
            $this->data['active_players'] = $statisticsService->getActivePlayersAmount($date);
            $this->data['incremental_players'] = $statisticsService->getIncrementalPlayersAmount($date);
            $this->data['one_day_remained'] = $statisticsService->getRemainedData($date, 1);
            $this->data['one_week_remained'] = $statisticsService->getRemainedData($date, 7);
            $this->data['two_weeks_remained'] = $statisticsService->getRemainedData($date, 14);
            $this->data['one_month_remained'] = $statisticsService->getRemainedData($date, 30);
            $this->data['card_consumed_data'] = $statisticsService->getCardConsumedData($date);
            $this->data['card_bought_data'] = $statisticsService->getCardBoughtData($date);
            $this->data['card_consumed_sum'] = $statisticsService->getCardConsumedSum($date);
            $this->data['card_bought_sum'] = $statisticsService->getCardBoughtSum($date);
        } else {
            //如果时间为历史时间，则从数据库中取数据
            $statement = StatementDaily::whereDate('date', $date)->first();
            if (empty($statement)) {
                throw new CustomException("{$date}: 无此日期的数据");
            }
            $this->data = array_merge($this->data, $statement->toArray());
        }

        $this->addLog('查看报表数据总览');

        return $this->data;
    }

    /**
     * 实时数据
     * @param AdminRequest $request
     * @return array
     */
    public function showRealTimeData(AdminRequest $request)
    {
        $statisticsService = app(StatisticsService::class);
        //实时数据
        $realTimeData = [
            'total_players_amount' => $statisticsService->getTotalPlayersAmount(),
            'online_players_amount' => $statisticsService->getOnlinePlayersAmount(),
            'in_game_players_amount' => $statisticsService->getInGamePlayersAmount(),
        ];
        $this->addLog('查看实时报表数据');

        return $realTimeData;
    }

    /**
     * 导出
     * @param AdminRequest $request
     */
    public function exportData2Excel(AdminRequest $request)
    {
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
        ]);
        $realTimeData = $this->showRealTimeData($request);
        $summaryData = $this->index($request);

        $this->addLog('导出实时报表数据');

        $filename = '数据总览_' . $request->input('date');
        $data = $this->buildExcelData($realTimeData, $summaryData);

        Excel::create($filename, function ($excel) use ($data) {
            $excel->sheet('数据总览', function ($sheet) use ($data) {
                foreach ($data as $k => $v) {
                    $sheet->appendRow([$k, $v]);
                }
            });
        })->export('xls');
    }

    protected function buildExcelData($realTimeData, $summaryData)
    {
        $data = [];
        $data['累计玩家'] = $realTimeData['total_players_amount'];
        $data['在线人数'] = $realTimeData['online_players_amount'];
        $data['游戏中人数'] = $realTimeData['in_game_players_amount'];

        $data['平均在线'] = $summaryData['average_online_players'];
        $data['日高峰'] = $summaryData['peak_online_players'];
        //$data['游戏中最高玩家数'] = $summaryData['peak_in_game_players'];
        $data['活跃玩家'] = $summaryData['active_players'];
        $data['新增玩家'] = $summaryData['incremental_players'];

        $oneDayRemainData = explode('|', $summaryData['one_day_remained']);
        $data['次日留存'] = $oneDayRemainData[2] . '% (' . $oneDayRemainData[0] . '/' . $oneDayRemainData[1] . ')';
        $oneWeekRemainData = explode('|', $summaryData['one_week_remained']);
        $data['7日留存'] = $oneWeekRemainData[2] . '% (' . $oneWeekRemainData[0] . '/' . $oneWeekRemainData[1] . ')';
        $twoWeeksRemainData = explode('|', $summaryData['two_weeks_remained']);
        $data['14日留存'] = $twoWeeksRemainData[2] . '% (' . $twoWeeksRemainData[0] . '/' . $twoWeeksRemainData[1] . ')';
        $oneMonthRemainData = explode('|', $summaryData['one_month_remained']);
        $data['30日留存'] = $oneMonthRemainData[2] . '% (' . $oneMonthRemainData[0] . '/' . $oneMonthRemainData[1] . ')';

        $cardConsumedData = explode('|', $summaryData['card_consumed_data']);
        $data['日耗卡'] = $cardConsumedData[0];
        $data['平均耗卡'] = $cardConsumedData[2];

        $cardBoughtData = explode('|', $summaryData['card_bought_data']);
        $data['日购卡'] = $cardBoughtData[0];
        $data['平均购卡'] = $cardBoughtData[2];

        $data['购卡总数'] = $summaryData['card_bought_sum'];
        $data['耗卡总数'] = $summaryData['card_consumed_sum'];
        $data['当月购卡累计人数'] = $summaryData['monthly_card_bought_players'];
        $data['当月购卡总数'] = $summaryData['monthly_card_bought_sum'];

        return $data;
    }

    public function hourly(AdminRequest $request)
    {
        $dateFormat = 'Y-m-d H:00';

        $result = $this->prepareData($dateFormat);

        OperationLogs::add(Auth::id(), $request->path(), $request->method(),
            '查看每小时流水报表', $request->header('User-Agent'));

        return $this->paginateData($result, $request->get('page', 1));
    }

    public function daily(AdminRequest $request)
    {
        $dateFormat = 'Y-m-d';

        $result = $this->prepareData($dateFormat);

        OperationLogs::add(Auth::id(), $request->path(), $request->method(),
            '查看每日流水报表', $request->header('User-Agent'));

        return $this->paginateData($result, $request->get('page', 1));
    }

    public function monthly(AdminRequest $request)
    {
        $dateFormat = 'Y-m';

        $result = $this->prepareData($dateFormat);

        OperationLogs::add(Auth::id(), $request->path(), $request->method(),
            '查看每月流水报表', $request->header('User-Agent'));

        return $this->paginateData($result, $request->get('page', 1));
    }

    protected function prepareData($dateFormat)
    {

        $agentPurchasedData = $this->fetchAgentPurchasedData($dateFormat);  //获取总的道具购买量
        $playerConsumedData = $this->fetchPlayerConsumedData($dateFormat);  //获取给玩家充值的消耗量

        $mergedData = $this->mergeData($agentPurchasedData, $playerConsumedData);   //数据合并
        $sortedData = $this->sortData($mergedData);     //数据排序，以时间倒序

        return $this->fillData($sortedData);         //填充数据，将需要的key补满，数据补0
    }

    protected function fetchAgentPurchasedData($dateFormat)
    {
        $cardData = $this->fetChData('App\Models\TopUpAdmin', $this->cardTypeId, $this->cardPurchasedKey, $dateFormat);
        $coinData = $this->fetchData('App\Models\TopUpAdmin', $this->coinTypeId, $this->coinPurchasedKey, $dateFormat);

        return $this->mergeData($cardData, $coinData);
    }

    protected function fetchPlayerConsumedData($dateFormat)
    {
        $cardData = $this->fetChData('App\Models\TopUpPlayer', $this->cardTypeId, $this->cardConsumedKey, $dateFormat);
        $coinData = $this->fetchData('App\Models\TopUpPlayer', $this->coinTypeId, $this->coinConsumedKey, $dateFormat);

        return $this->mergeData($cardData, $coinData);
    }

    //从数据库拿数据，以时间为key，以数组返回
    protected function fetchData($db, $itemType, $keyName, $dateFormat)
    {
        /* sql查询的形式
        //select SUM(amount) as total, DATE_FORMAT(created_at, "%Y-%m-%d %H:00") as create_date
        //from `top_up_admin` where `type` = ? group by `create_date`
        $coinData = DB::table('top_up_admin')
            ->select(DB::raw('SUM(amount) as coin_total, DATE_FORMAT(created_at, "%Y-%m-%d %H:00") as create_date'))
            ->where('type', $this->coinTypeId)
            ->groupBy('create_date')
            ->get()
            ->keyBy('create_date');
        */

        //集合查询的形式，由php来做计算，降低mysql压力
        return $db::get()
            ->where('type', $itemType)
            ->filter(function ($value, $key) {
                //过滤管理员自己给自己充值的。有可能用户被删除了，但是充值记录还在，此情况也得判断
                if ($value->receiver_id && User::find($value->receiver_id)) {
                    return (string)User::find($value->receiver_id)->group->id !== $this->adminGid;
                }

                return $value;
            })
            ->groupBy(function ($date) use ($dateFormat) {
                return Carbon::parse($date->created_at)->format($dateFormat);
            })
            ->map(function ($item, $key) use ($keyName) {
                return [
                    $keyName => $item->sum('amount'),
                    'date' => $key,
                ];
            })->toArray();
    }

    //把两个数组的数据做合并
    protected function mergeData($firstData, $lastData)
    {
        $firstDataCopy = $firstData;  //需要有个中间变量，不然闭包里面直接更改原数组的元素会出问题
        array_walk($firstData, function ($value, $key) use (&$lastData, &$firstDataCopy) {
            if (array_key_exists($key, $lastData)) {
                $lastData[$key] = array_merge($lastData[$key], $value);
                unset($firstDataCopy[$key]);
            }
        });

        $result = array_merge($lastData, $firstDataCopy);

        return $result;
    }

    protected function sortData($data)
    {
        krsort($data);                  //按照时间倒序排序

        return array_values($data);     //返回索引数组
    }

    protected function fillData($data)
    {
        $requiredKeys = [                    //每个单元数据中必须的key名
            $this->cardPurchasedKey,
            $this->coinPurchasedKey,
            $this->cardConsumedKey,
            $this->coinConsumedKey,
            'date',
        ];

        return array_map(function ($item) use ($requiredKeys) {
            $existKeys = array_keys($item);                          //此单元中已经存在的key
            $shouldFilledKeys = array_diff($requiredKeys, $existKeys);    //待填充0的key名
            array_walk($shouldFilledKeys, function ($shouldFilledKey) use (&$item) {
                $item[$shouldFilledKey] = 0;
            });

            return $item;
        }, $data);
    }

    //准备分页数据
    protected function paginateData($data, $page)
    {
        return Paginator::paginate($data, $this->per_page, $page);
    }

    //每小时流水的图表数据
    public function hourlyChart(AdminRequest $request)
    {
        $dateFormat = 'Y-m-d H:00';

        $result = $this->prepareData($dateFormat);
        $result = array_combine(array_column($result, 'date'), $result);
        ksort($result);

        OperationLogs::add(Auth::id(), $request->path(), $request->method(),
            '查看每小时流水图表', $request->header('User-Agent'));

        return $result;
    }
}