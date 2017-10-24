<?php

namespace App\Http\Controllers\Admin\Game;

use App\Http\Requests\AdminRequest;
use App\Models\Game\Npc;
use App\Models\Game\NpcDataMap;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AiController extends Controller
{
    use NpcDataMap;

    public function show(AdminRequest $request)
    {
        return Npc::all();
    }

    public function getGameTypeMap(AdminRequest $request)
    {
        return $this->gameTypeMap;
    }
}
