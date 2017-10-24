<?php

namespace App\Http\Controllers\Admin\Game;

use App\Http\Requests\AdminRequest;
use App\Models\Game\Npc;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AiController extends Controller
{
    public function show(AdminRequest $request)
    {
        return Npc::all();
    }
}
