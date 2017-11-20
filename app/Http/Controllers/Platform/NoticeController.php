<?php

namespace App\Http\Controllers\Platform;

use App\Models\GameNotificationLogin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NoticeController extends Controller
{
    //获取登录公告
    public function showLoginNotice(Request $request)
    {
        if (! $request->has('id')) {
            return 'not found';
        }

        $this->validate($request, [
            'id' => 'integer',
        ]);

        $notice = GameNotificationLogin::where('id', $request->input('id'))
            ->first();

        if (empty($notice)) {
            return 'not found';
        }

        return view('api.cnotice', [
            'title' => $notice->title,
            'content' => $notice->content,
        ]);
    }
}
