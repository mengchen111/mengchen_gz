<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Models\GroupIdMap;
use App\Services\GameServer;
use Illuminate\Http\Request;
use App\Http\Requests\AdminRequest;
use App\Models\User;
use App\Models\TopUpAdmin;
use App\Models\TopUpAgent;
use App\Models\TopUpPlayer;
use App\Models\Game\Player;
use App\Models\ItemType;
use Illuminate\Support\Facades\Validator;
use App\Models\OperationLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TopUpController extends Controller
{
    use GroupIdMap;

    protected $per_page = 15;
    protected $order = ['id', 'desc'];
    protected $cardItemId = 1030005;    //房卡在游戏库中的id号
    protected $topUpItemApi = '/role/addItem';
    protected $subItemApi = '/role/subItem';
    protected $itemIdMap = [        //后台与游戏后端的道具id的映射关系
        1 => 1030005,   //后台房卡id 1对应游戏数据库房卡id 1030005
        2 => 1,
    ];

    public function __construct(Request $request)
    {
        $this->per_page = $request->per_page ?: $this->per_page;
        $this->order = $request->sort ? explode('|', $request->sort) : $this->order;
    }

    //给代理商充值（自己的下级）
    public function topUp2Agent(AdminRequest $request, $receiver, $type, $amount)
    {
        Validator::make($request->route()->parameters,[
            'receiver' => 'required|string|exists:users,account',
            'type' => 'required|integer|exists:item_type,id',
            'amount' => 'required|integer|not_in:0',
        ])->validate();

        $provider = User::with(['inventory' => function ($query) use ($type) {
            $query->where('item_id', $type);
        }])->find($request->user()->id);
        $receiverModel = User::with(['inventory' => function ($query) use ($type) {
            $query->where('item_id', $type);
        }])->where('account', $receiver)->firstOrFail();

        if (! $receiverModel->isChild(Auth::id())) {
            throw new CustomException('只能给自己的下级代理商充值');
        }

        if (! $this->checkStock($provider, $amount)) {
            throw new CustomException('库存不足，无法充值');
        }

        $this->topUp4Child($request, $provider, $receiverModel, $type, $amount);

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '管理员给代理商充值', $request->header('User-Agent'), json_encode($request->route()->parameters));

        return [
            'message' => '充值成功',
        ];
    }

    protected function checkStock(User $provider, $amount)
    {
        return (! empty($provider->inventory)) and $provider->inventory->stock >= $amount;
    }

    protected function topUp4Child($request, $provider, $receiver, $type, $amount)
    {
        return DB::transaction(function () use ($request, $provider, $receiver, $type, $amount){
            //记录充值流水
            TopUpAdmin::create([
                'provider_id' => $request->user()->id,
                'receiver_id' => $receiver->id,
                'type' => $type,
                'amount' => $amount,
            ]);

            //更新库存
            if (empty($receiver->inventory)) {
                $receiver->inventory()->create([
                    'user_id' => $receiver->id,
                    'item_id' => $type,
                    'stock' => $amount,
                ]);
            } else {
                $totalStock = $amount + $receiver->inventory->stock;
                $receiver->inventory->update([
                    'stock' => $totalStock,
                ]);
            }

            //减管理员的库存
            $leftStock = $provider->inventory->stock - $amount;
            $provider->inventory->update([
                'stock' => $leftStock,
            ]);
        });
    }

    //管理员给代理商的充值记录
    public function admin2AgentHistory(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '管理员查看其充值记录', $request->header('User-Agent'), json_encode($request->all()));

        //搜索代理商
        if ($request->has('filter')) {
            $receivers = array_column(User::where('account', 'like', "%{$request->filter}%")->get()->toArray(), 'id');
            if (empty($receivers)) {
                return null;
            }
            return  TopUpAdmin::with(['provider', 'receiver', 'item'])
                ->whereIn('receiver_id', $receivers)
                ->orderBy($this->order[0], $this->order[1])
                ->paginate($this->per_page);
        }

        return TopUpAdmin::with(['provider', 'receiver', 'item'])
            ->orderBy($this->order[0], $this->order[1])
            ->paginate($this->per_page);
    }

    //上级代理商给下级的充值记录
    public function agent2AgentHistory(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '管理员查看代理商充值记录', $request->header('User-Agent'), json_encode($request->all()));

        //搜索代理商，查找字符串包括发放者和接收者
        if ($request->has('filter')) {
            $accounts = array_column(User::where('account', 'like', "%{$request->filter}%")->get()->toArray(), 'id');
            if (empty($accounts)) {
                return null;
            }
            return TopUpAgent::with(['provider', 'receiver', 'item'])
                ->whereIn('provider_id', $accounts)
                ->whereIn('receiver_id', $accounts, 'or')
                ->orderBy($this->order[0], $this->order[1])
                ->paginate($this->per_page);
        }

        return TopUpAgent::with(['provider', 'receiver', 'item'])
            ->orderBy($this->order[0], $this->order[1])
            ->paginate($this->per_page);
    }

    //代理商给玩家的充值记录
    public function agent2PlayerHistory(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '管理员查看代理商给玩家充值记录', $request->header('User-Agent'), json_encode($request->all()));

        //搜索provider
        if ($request->has('filter')) {
            $accounts = array_column(User::where('account', 'like', "%{$request->filter}%")->get()->toArray(), 'id');
            if (empty($accounts)) {
                return null;
            }
            return TopUpPlayer::with(['provider', 'item'])
                ->whereIn('provider_id', $accounts)
                ->orderBy($this->order[0], $this->order[1])
                ->paginate($this->per_page);
        }

        return TopUpPlayer::with(['provider', 'item'])
            ->orderBy($this->order[0], $this->order[1])
            ->paginate($this->per_page);
    }

    /**
     * @param Request $request
     * @param $player   玩家id
     * @param $type     道具类型
     * @param $amount   数量
     */
    public function topUp2Player(AdminRequest $request, $player, $type, $amount)
    {
        Validator::make($request->route()->parameters,[
            'player' => 'required|string|exists:mysql-game.role,rid',
            'type' => 'required|integer|exists:item_type,id',
            'amount' => 'required|integer|not_in:0',
        ])->validate();

        $provider = User::with(['inventory' => function ($query) use ($type) {
            $query->where('item_id', $type);
        }])->find($request->user()->id);

        if (! $this->checkStock($provider, $amount)) {
            throw new CustomException('库存不足，无法充值');
        }

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '管理员给玩家充值[减值]', $request->header('User-Agent'), json_encode($request->route()->parameters));

        //减玩家道具
        if (preg_match('/-/', $amount)) {
            $amount = (int) trim($amount, '-');
            $this->cutStock4Player($request, $player, $type, $amount);
            return [
                'message' => '减库存成功',
            ];
        }

        $this->topUp4Player($request, $provider, $player, $type, $amount);

        return [
            'message' => '充值成功',
        ];
    }

    /**
     * @param $provider 管理员模型
     * @param $player   玩家模型
     * @param $type     道具id
     * @param $amount   道具数量
     */
    protected function topUp4Player($request, $provider, $player, $type, $amount)
    {
        return DB::transaction(function () use ($request, $provider, $player, $type, $amount) {
            //更新库存（调用充值接口）
            GameServer::request('POST', $this->topUpItemApi, [
                'playerid' => $player,
                'id' => $this->itemIdMap[$type],
                'count' => $amount,
            ]);

            //减管理员的库存
            $leftStock = $provider->inventory->stock - $amount;
            $provider->inventory->update([
                'stock' => $leftStock,
            ]);

            //记录充值流水
            TopUpPlayer::create([
                'provider_id' => $request->user()->id,
                'player' => $player,
                'type' => $type,
                'amount' => $amount,
            ]);
        });
    }

    protected function cutStock4Player($request, $player, $type, $amount)
    {
        if ((int)$type === 1) {
            $playerModel = Player::with('card')->find($player);
            if (empty($playerModel)) {
                throw new CustomException('库存不足，无法减少');
            }
            $stock = $playerModel->card->count;
        } else {
            $playerModel = Player::find($player);
            $stock = $playerModel->gold;
        }

        if ($stock < $amount) {
            throw new CustomException('库存不足，无法减少');
        }

        $admin = User::with(['inventory' => function ($query) use ($type) {
            $query->where('item_id', $type);
        }])->find($this->adminId);

        return DB::transaction(function () use ($request, $admin, $player, $type, $amount) {
            //更新库存（调用减库存接口）
            GameServer::request('POST', $this->subItemApi, [
                'playerid' => $player,
                'id' => $this->itemIdMap[$type],
                'count' => $amount,
            ]);

            //加管理员的库存
            $admin->inventory->stock += $amount;
            $admin->inventory->save();

            //记录充值流水
            TopUpPlayer::create([
                'provider_id' => $this->adminId,
                'player' => $player,
                'type' => $type,
                'amount' => '-' . $amount,
            ]);
        });
    }
}