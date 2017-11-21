<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Http\Requests\AdminRequest;
use App\Models\Platform\Server;
use App\Services\Paginator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OperationLogs;

class ServerController extends Controller
{
    public function show(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看游戏后端服务器列表', $request->header('User-Agent'), json_encode($request->all()));

        $serverList = Server::all();
        return $serverList->map(function ($server) {
            return collect($server->toArray())->only([
                //for ai list page
                'id',
                'area',
                'area_name',
                'name',
            ]);
        });
    }

    public function serverList(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看游戏服务器列表[带分页]', $request->header('User-Agent'), json_encode($request->all()));

        $serverList = Server::all()->toArray();
        return Paginator::paginate($serverList, $this->per_page, $this->page);
    }
}
