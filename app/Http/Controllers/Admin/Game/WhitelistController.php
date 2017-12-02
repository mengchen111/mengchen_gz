<?php

namespace App\Http\Controllers\Admin\Game;

use App\Http\Requests\AdminRequest;
use App\Services\Paginator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GameServer;
use App\Models\OperationLogs;

class WhitelistController extends Controller
{
    protected $per_page = 15;
    protected $page = 1;
    protected $order = ['rid', 'desc'];
    protected $addWhitelistUri = 'whitelist/addWhiteList';
    protected $listWhitelistUri = 'whitelist/getWhiteList';
    protected $editWhitelistUri = 'whitelist/editWhiteList';
    protected $deleteWhitelistUri = 'whitelist/deleteWhiteList';

    public function __construct(Request $request)
    {
        $this->per_page = $request->per_page ?: $this->per_page;
        $this->page = $request->page ?: $this->page;
        $this->order = $request->sort ? explode('|', $request->sort) : $this->order;
    }

    public function addWhitelist(AdminRequest $request)
    {
        $formData = $this->filterWhitelistForm($request);

        GameServer::request('POST', $this->addWhitelistUri, $formData);

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '添加白名单', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'message' => '添加白名单成功',
        ];
    }

    protected function filterWhitelistForm($request)
    {
        $this->validate($request, [
            'playerid' => 'required|integer|exists:mysql-game.role,rid',
            'winrate' => 'required|integer|between:1,100',
        ]);

        return $request->intersect([
            'playerid', 'winrate',
        ]);
    }

    public function editWhiteList(AdminRequest $request)
    {
        $formData = $this->filterWhitelistForm($request);

        GameServer::request('POST', $this->editWhitelistUri, $formData);

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '编辑白名单', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'message' => '编辑白名单成功',
        ];
    }

    public function listWhitelist(AdminRequest $request)
    {
        $list = GameServer::request('GET', $this->listWhitelistUri)['data'];

        if ($request->has('filter')) {
            $filter = $request->filter;
            $list = collect($list)->filter(function ($value, $key) use ($filter) {
                return preg_match("/${filter}/", $value['playerid']);
            })->toArray();
        }
        return Paginator::paginate($list);
    }

    public function deleteWhitelist(AdminRequest $request)
    {
        $formData = $this->filterDelWhitelistForm($request);

        GameServer::request('POST', $this->deleteWhitelistUri, $formData);

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '删除白名单', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'message' => '删除白名单成功',
        ];
    }

    protected function filterDelWhitelistForm($request)
    {
        $params = [];
        $this->validate($request, [
            'playerid' => 'required|integer|exists:mysql-game.role,rid',
        ]);
        $params['playerid'] = $request->input('playerid');
        return $params;
    }
}
