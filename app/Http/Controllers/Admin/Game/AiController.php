<?php

namespace App\Http\Controllers\Admin\Game;

use App\Http\Requests\AdminRequest;
use App\Models\Game\Npc;
use App\Models\Game\NpcDataMap;
use App\Models\Log\NpcDispatchLog;
use App\Services\GameServer;
use App\Services\Paginator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OperationLogs;

class AiController extends Controller
{
    use NpcDataMap;

    protected $per_page = 15;
    protected $page = 1;
    protected $order = ['rid', 'desc'];
    protected $backendServerApi;
    protected $editAiUri = '/Npc/edit';
    protected $editAiDispatchUri = '/Npc/dispatch';
    protected $switchAiDispatchUri = '/Npc/change';     //停用启用
    protected $addAiUri = '/Npc/add';

    public function __construct(Request $request)
    {
        $this->per_page = $request->per_page ?: $this->per_page;
        $this->page = $request->page ?: $this->page;
        $this->order = $request->sort ? explode('|', $request->sort) : $this->order;
        $this->backendServerApi = config('custom.game_server_api_address');
    }

    public function show(AdminRequest $request)
    {
        $result = Npc::all();
        if ($request->has('game_type')) {
            $result = $result->where('game_type', $request->game_type);
        }
        if ($request->has('status')) {
            $result = $result->where('status', $request->status);
        }
        $result = $result->reverse()->toArray();    //倒序排列

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看AI列表', $request->header('User-Agent'), json_encode($request->all()));

        return Paginator::paginate($result, $this->per_page, $this->page);
    }

    public function showDispatch(AdminRequest $request)
    {
        $result = NpcDispatchLog::all();
        if ($request->has('game_type')) {
            $result = $result->where('game_type', $request->game_type);
        }
        if ($request->has('is_open')) {
            $result = $result->where('is_open', $request->is_open);  //查询开启状态
        }
        $result = $result->reverse()->toArray();                    //倒序排列

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看AI调度列表', $request->header('User-Agent'), json_encode($request->all()));

        return Paginator::paginate($result, $this->per_page, $this->page);
    }

    public function getMaps(AdminRequest $request)
    {
        $map = [];
        $map['game_type'] = collect($this->gameTypeMap)
            ->only([
                '14', '15', '16', '17',     //只返回使用到的几种游戏类型
            ]);
        $map['status_type'] = $this->statusMap;
        $map['room_type'] = $this->roomTypeMap;

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看游戏类型映射关系', $request->header('User-Agent'), json_encode($request->all()));

        return $map;
    }

    public function edit(AdminRequest $request)
    {
        $formData = $this->filterEditForm($request);
        $api = $this->backendServerApi . $this->editAiUri;
        $gameServer = new GameServer($api);

        $gameServer->request('POST', $formData);    //发送编辑请求

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '编辑AI', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'message' => '编辑AI成功',
        ];
    }

    public function editDispatch(AdminRequest $request)
    {
        $formData = $this->filterEditDispatchForm($request);
        $api = $this->backendServerApi . $this->editAiDispatchUri;
        $gameServer = new GameServer($api);

        $gameServer->request('POST', $formData);    //发送编辑请求

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '编辑AI调度', $request->header('User-Agent'), json_encode($request->all()));

        //TODO 编辑完成之后后端数据库添加了一条记录，而不是更新
        return [
            'message' => '编辑AI调度成功',
        ];
    }

    public function switchAiDispatch(AdminRequest $request, $id, $switch)
    {
        $api = $this->backendServerApi . $this->switchAiDispatchUri;

        $gameServer = new GameServer($api);
        $gameServer->request('POST', [
            'logId' => $id,
            'id' => $request->ids,
            'isOpen' => $switch
        ]);

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            'AI调度启用(停用)', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'message' => '操作成功',
        ];
    }

    protected function filterEditDispatchForm($request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'ids' => 'required|string',
            'golds' => 'required|string',
            'theme' => 'required|string',
            'game_type' => 'required|in:' . implode(',', $this->gameTypeMap),
            'room_type' => 'required|in:' . implode(',', $this->roomTypeMap),
            'do_start_date' => 'required|date_format:Y-m-d',
            'do_end_date' => 'required|date_format:Y-m-d',
            'do_start_time' => 'required|date_format:H:i:s',
            'do_end_time' => 'required|date_format:H:i:s',
            'is_all_day' => 'required|integer|in:0,1',
            'server_id' => 'required|integer',
        ]);

        $formData = $request->intersect([
            'theme',
        ]);

        //构建POST请求的数据结构
        $formData['lodId'] = $request->id;
        $formData['id'] = $request->ids;
        $formData['gold'] = $request->golds;
        $formData['serverId'] = $request->server_id;
        $formData['gameType'] = (string) array_search($request->game_type, $this->gameTypeMap);
        $formData['roomType'] = (string) array_search($request->room_type, $this->roomTypeMap);
        $formData['sdate'] = Carbon::parse($request->do_start_date)->timestamp;
        $formData['edate'] = Carbon::parse($request->do_end_date)->timestamp;
        $formData['isAllDay'] = $request->is_all_day;
        $formData['creator'] = $request->user()->account;

        $startTime = explode(':', $request->do_start_time);
        $formData['stime'] = $startTime[0] * 3600 + $startTime[1] * 60 + $startTime[2];

        $endTime = explode(':', $request->do_end_time);
        $formData['etime'] = $endTime[0] * 3600 + $endTime[1] * 60 + $endTime[2];

        return $formData;
    }

    protected function filterEditForm($request)
    {
        $this->validate($request, [
            'rid' => 'required|integer',
            'nick' => 'required',
            'diamond' => 'required|integer',
            'crystal' => 'required|integer',
            'exp' => 'required|integer',
            //'db' => 'required|integer',
        ]);

        $formData = $request->intersect([
            'nick', 'diamond', 'exp'
        ]);
        //构建接口需要的数据结构
        $formData['lottery'] = $request->crystal;       //奖券
        //$formData['server_id'] = $request->db;        //经测试不用传此参数
        $formData['id'] = $request->rid;
        //$formData['head'] = -2;                       //经测试不用传此参数

        return $formData;
    }
}
