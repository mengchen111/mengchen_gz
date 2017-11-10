<?php

namespace App\Http\Controllers\Platform;

use App\Models\Platform\ClientVersion;
use App\Models\Platform\ReviewWhiteList;
use App\Models\Platform\Role;
use App\Models\Platform\Server;
use App\Models\Platform\WhiteList;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OperationLogs;

class ServerListController extends Controller
{
    protected $serverTypeMap = [
        'ios' => 1,
        'android' => 2,
        'window' => 3,
    ];
    protected $clientIp;            //客户端ip
    protected $serverRoles = [];    //角色列表
    protected $lastLoginSid = 0; //最近一次登陆的服务器id
    protected $lastLoginRid = 0; //最近一次登陆的角色id
    protected $rateSid = 0;         //推荐服务器id
    protected $rateSidNoUpdate = 0;
    protected $isWhite = 0;         //查看是否是白名单
    protected $serverType = 0;      //服务器类型
    protected $tsServerId = 0;      //提审强制跳转服id
    protected $serverList = [];     //服务器列表

    public function __construct(Request $request)
    {
        //$this->filterRequest($request);
        $this->clientIp = $request->getClientIp();
        $this->getServerRoles($request);
        $this->checkIsWhite($request);
        $this->getServerType($request);
        $this->getServerList($request);
    }

    public function show(Request $request)
    {
        OperationLogs::add(1, $request->path(), $request->method(),
            '[platform]查看服务器列表', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'code' => 0,
            'is_white' => $this->isWhite,
            'rate_sid' => strval($this->rateSid),
            'last_login_rid' => $this->lastLoginRid,
            'last_login_sid' => (string) $this->lastLoginSid,
            'server_list' => $this->serverList,
        ];
    }

    protected function filterRequest($request)
    {
        $this->validate($request, [
            'open_id' => 'string',  //用户渠道账号
            'area' => 'string',     //客户端地区
            'device_type' => 'string',  //客户端设备类型
            'platform' => 'string',     //客户端渠道
            'version' => 'string',      //客户端版本号
        ]);
    }

    protected function checkIsWhite($request)
    {
        if ($request->has('open_id')) {
            $whiteList = WhiteList::where('ip', $this->clientIp)
                ->where('open_id', $request->open_id)
                ->get()
                ->toArray();
        } else {
            $whiteList = WhiteList::where('ip', $this->clientIp)
                ->get()
                ->toArray();
        }
        if (! empty($whiteList)) {
            $this->isWhite = 1;
        }
    }

    //获取此渠道帐号在不同服务器的角色列表基本信息（以server id为key）
    protected function getServerRoles($request)
    {
        if ($request->has('open_id')) {
            $roles = Role::where('open_id', $request->open_id)
                ->orderBy('login_time', 'desc')
                ->get()
                ->toArray();

            if (! empty($roles)) {
                $this->lastLoginRid = $roles[0]['rid'];
                $this->lastLoginSid = $roles[0]['server_id'];

                foreach ($roles as $role) {
                    $serverId = $role['server_id'];
                    if (! isset($this->serverRoles[$serverId])) {
                        $this->serverRoles[$serverId] = [];
                    }
                    $role['nickname'] = str_replace("/", "", $role['nickname']);
                    array_push($this->serverRoles[$serverId], $role);
                }
            }
        }
    }

    //如果客户端版本处于审核状态, 则只获取审核服
    protected function getServerType($request)
    {
        if ($request->has('area') AND $request->has('device_type') AND $request->has('platform') AND $request->has('version')) {
            $clientVersion = ClientVersion::where('area', $request->area)
                ->where('device_type', $request->device_type)
                ->where('platform', $request->platform)
                ->where('ver_id', $request->version)
                ->first();

            if ($clientVersion) {
                $clientVersion = $clientVersion->toArray();

                //提审强制跳转服id
                $this->tsServerId = $clientVersion['server_id'];

                if (2 == $clientVersion['status']) {
                    if ($clientVersion['need_review_white'] != 1) {
                        $this->serverType = $this->serverTypeMap[$request->device_type];
                    } else {
                        //此客户端审核版本需要审核白名单才能进入审核服
                        if ($request->has('open_id')) {
                            $reviewWhiteList = ReviewWhiteList::where('ip', $this->clientIp)
                                ->where('open_id', $request->open_id)
                                ->get()
                                ->toArray();
                        } else {
                            $reviewWhiteList = ReviewWhiteList::where('ip', $this->clientIp)
                                ->get()
                                ->toArray();
                        }

                        if (! empty($reviewWhiteList)) {
                            $this->serverType = $this->serverTypeMap[$request->device_type];
                        }
                    }
                }
            }
        }

        // APPLE公司 IP为 17.开头的, 强制只返回审核服
        if ($request->device_type == 'ios' AND substr($this->clientIp, 0, 3) == '17.') {
            $this->serverType = $this->serverTypeMap[$request->device_type];
        }
    }

    protected function getServerList($request)
    {
        $servers = Server::orderBy('rate', 'DESC')->get();
        if (! $this->isWhite) {     //如果不再白名单里面
            if ($this->tsServerId) {
                $servers = $servers->where('id', $this->tsServerId);
            } else {
                $servers = $servers->where('type', $this->serverType);
            }
            $servers = $servers->filter(function ($value, $key) {
                return $value['status'] > 0;
            });
        }
        $servers = $servers->toArray();

        if (!empty($servers)) {
            foreach ($servers as $server) {
                $serverId = $server['id'];
                $result = [
                    'area_name' => $server['area_name'],
                    'name' => $server['name'],
                    'id' => (string) $server['id'],
                    'host' => $server['host'],
                    'port' => (string) $server['port'],
                    'status' => (string) $server['status'],
                    'rate' => (string) $server['rate'],
                    'server_type' => (string) $server['type'],
                    'roles' => [],
                ];

                // 是否处于更新状态中
                if ($server['is_update'] == 1) {
                    $result['is_update'] = 1;
                    $result['is_update_msg'] = $server['is_update_msg'];
                }

                // 角色列表
                if (isset($this->serverRoles[$serverId])) {
                    $result['roles'] = $this->serverRoles[$serverId];
                }

                // 推荐服务器(返回获取的第一个服务器为推荐服务器)
                if ($server['status'] > 0 AND !$server['is_update'] AND !$this->rateSid) {
                    $this->rateSid = $serverId;
                }
                if ($server['status'] > 0 AND !$this->rateSidNoUpdate) {
                    $this->rateSidNoUpdate = $serverId;
                }

                array_push($this->serverList, $result);
            }

            if ($this->rateSidNoUpdate > 0 AND $this->rateSid == 0) {
                $this->rateSid = $this->rateSidNoUpdate;
            }
        }
    }
}
