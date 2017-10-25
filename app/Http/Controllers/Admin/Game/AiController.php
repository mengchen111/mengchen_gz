<?php

namespace App\Http\Controllers\Admin\Game;

use App\Exceptions\CustomException;
use App\Exceptions\GameServerException;
use App\Http\Requests\AdminRequest;
use App\Models\Game\Npc;
use App\Models\Game\NpcDataMap;
use App\Services\GameServer;
use App\Services\Paginator;
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
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看AI列表', $request->header('User-Agent'), json_encode($request->all()));

        $result = Npc::all();
        if ($request->has('game_type')) {
            $result = $result->where('game_type', $request->game_type);
        }
        if ($request->has('status')) {
            $result = $result->where('status', $request->status);
        }

        return Paginator::paginate($result->reverse()->toArray(), $this->per_page, $this->page);
    }

    public function getMaps(AdminRequest $request)
    {
        $map = [];
        $map['game_type'] = collect($this->gameTypeMap)
            ->only([
                '14', '15', '16', '17',     //只返回使用到的几种游戏类型
            ]);
        $map['status_type'] = $this->statusMap;

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
