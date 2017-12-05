<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdminRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ItemType;

class ItemController extends Controller
{
    public function show(AdminRequest $request)
    {
        return ItemType::paginate($this->per_page);
    }

    public function editPrice(AdminRequest $request, ItemType $item)
    {
        $this->validate($request, [
            'price' => 'required|integer|min:1',
        ]);
        $item->price = $request->input('price');
        $item->save();

        return [
            'message' => '更新道具价格成功',
        ];
    }
}
