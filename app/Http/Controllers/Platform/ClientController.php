<?php

namespace App\Http\Controllers\Platform;

use App\Exceptions\PlatformException;
use App\Models\Platform\ClientFeedback;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    //客户端错误日志
    public function collectClientErrorLog(Request $request)
    {
        //TODO
    }

    public function collectClientFeedback(Request $request)
    {
        $this->filterFeedbackForm($request);

        if ($request->hasFile('img')) {
            $img = $request->file('img')->store('feedback', 'platform');
        } else {
            $img = '';
        }

        $feedback = ClientFeedback::where('rid', $request->rid)
            ->where('type', $request->type)
            ->first();

        if (empty($feedback)) {
            ClientFeedback::create([
                'rid' => $request->rid,
                'type' => $request->type,
                'content' => $request->input('content') ?: '',
                'img' => $img,
                'create_time' => time(),
            ]);
        } else {
            $feedback->update([
                'content' => $request->input('content') ?: '',
                'img' => $img,
                'create_time' => time(),
            ]);
        }

        return [
            'code' => 0,
        ];
    }

    protected function filterFeedbackForm($request)
    {
        try {
            $this->validate($request, [
                'rid' => 'required|integer',
                'type' => 'required|integer',
                'content' => 'string|max:255',
                'img' => 'mimes:jpeg,jpg,bmp,png,gif|max:2048',
            ]);
        } catch (ValidationException $exception) {
            throw new PlatformException($exception->getMessage());
        }
    }
}
