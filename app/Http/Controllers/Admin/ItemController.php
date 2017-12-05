<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdminRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use App\Models\OperationLogs;

class ItemController extends Controller
{
    public function show(AdminRequest $request)
    {
        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '查看道具列表', $request->header('User-Agent'));

        return ItemType::paginate($this->per_page);
    }

    public function editPrice(AdminRequest $request, ItemType $item)
    {
        $this->validate($request, [
            'price' => 'required|integer|min:1',
        ]);
        $item->price = $request->input('price');
        $item->save();

        OperationLogs::add($request->user()->id, $request->path(), $request->method(),
            '编辑道具价格', $request->header('User-Agent'));

        return [
            'message' => '更新道具价格成功',
        ];
    }
}
