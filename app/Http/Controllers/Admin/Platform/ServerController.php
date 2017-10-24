<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Http\Requests\AdminRequest;
use App\Models\Platform\Server;
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
                'id',
                'area',
                'area_name',
                'name',
            ]);
        });
    }
}
