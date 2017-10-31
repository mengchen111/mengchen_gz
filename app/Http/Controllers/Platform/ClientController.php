<?php

namespace App\Http\Controllers\Platform;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public function collectClientErrorLog(Request $request)
    {
        //TODO
    }

    public function collectClientFeedback(Request $request)
    {
        $path = $request->file('test')->store('feedback', 'platform');
        return $path;
    }
}
