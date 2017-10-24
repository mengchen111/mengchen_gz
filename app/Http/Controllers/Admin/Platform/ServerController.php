<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Http\Requests\AdminRequest;
use App\Models\Platform\Server;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServerController extends Controller
{
    public function show(AdminRequest $request)
    {
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
