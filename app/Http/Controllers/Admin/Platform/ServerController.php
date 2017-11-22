<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Http\Requests\AdminRequest;
use App\Models\Platform\Server;
use App\Models\Platform\ServerDataMap;
use App\Services\Paginator;
use App\Services\Platform\EncryptionService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OperationLogs;

class ServerController extends Controller
{
    use ServerDataMap;

    protected $column = [
        'id', 'area', 'area_name', 'name', 'address_list', 'host', 'port', 'http_port', 'open_time',
        'rate', 'server_status', 'status_msg', 'h_mysql_host', 'h_mysql_port', 'h_mysql_user', 'h_mysql_passwd',
        'mysql_host', 'mysql_port', 'mysql_user', 'mysql_passwd', 'mysql_data_name', 'mysql_log_name',
        'server_type', 'can_see_value', 'is_cron_value',
    ];

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

    public function editServer(AdminRequest $request, Server $server)
    {
        $data = $this->buildData($request, $server);

        $server->update($data);

        return [
            'message' => '编辑游戏服成功'
        ];
    }

    protected function buildData($request, $server)
    {
        $data = $request->intersect($this->column);

        if (isset($data['h_mysql_passwd'])) {
            $data['h_mysql_passwd'] = EncryptionService::encryptDbPass($data['h_mysql_passwd']);
        }
        if (isset($data['mysql_passwd'])) {
            $data['mysql_passwd'] = EncryptionService::encryptDbPass($data['mysql_passwd']);
        }
        if (isset($data['server_status'])) {
            $data['status'] = array_search($data['server_status'], $server->serverStatusMap);
            unset($data['server_status']);
        }
        if (isset($data['server_type'])) {
            $data['type'] = array_search($data['server_type'], $server->serverTypeMap);
            unset($data['server_type']);
        }
        if (isset($data['can_see_value'])) {
            $data['can_see'] = array_search($data['can_see_value'], $server->yesOrNoMap);
            unset($data['can_see_value']);
        }
        if (isset($data['is_cron_value'])) {
            $data['is_cron'] = array_search($data['is_cron_value'], $server->yesOrNoMap);
            unset($data['is_cron_value']);
        }
        
        return $data;
    }

    public function getServerDataMap(AdminRequest $request)
    {
        $data = [];
        $data['server_status_map'] = $this->serverStatusMap;
        $data['server_type_map'] = $this->serverTypeMap;
        $data['area_list'] = $this->areaList;
        return $data;
    }
}
