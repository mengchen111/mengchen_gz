<?php

namespace App\Http\Controllers\Admin\Game;

use App\Http\Requests\AdminRequest;
use App\Models\Game\Npc;
use App\Models\Game\NpcDataMap;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OperationLogs;

class AiController extends Controller
{
    use NpcDataMap;

    protected $per_page = 15;
    protected $order = ['rid', 'desc'];

    public function __construct(Request $request)
    {
        $this->per_page = $request->per_page ?: $this->per_page;
        $this->order = $request->sort ? explode('|', $request->sort) : $this->order;
    }

    public function show(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看AI列表', $request->header('User-Agent'), json_encode($request->all()));

        return Npc::paginate($this->per_page);
    }

    public function getGameTypeMap(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看游戏类型映射关系', $request->header('User-Agent'), json_encode($request->all()));

        return $this->gameTypeMap;
    }
}
