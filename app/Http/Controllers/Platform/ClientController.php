<?php

namespace App\Http\Controllers\Platform;

use App\Exceptions\PlatformException;
use App\Models\Platform\ClientErrorLog;
use App\Models\Platform\ClientFeedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    //客户端错误日志
    public function collectClientErrorLog(Request $request)
    {
        $this->filterErrorLogForm($request);
        $data = $this->buildErrorLogData($request);

        ClientErrorLog::create($data);

        return [
            'code' => 0,
        ];
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

    protected function filterErrorLogForm($request)
    {
        try {
            $this->validate($request, [
                'sid' => 'integer',
                'platform' => 'string',
                'cid' => 'integer',
                'device_type' => 'string',
                'version' => 'string',
                'error_type' => 'integer',
                'match_id' => 'string',
                'msg' => 'required|string',
            ]);
        } catch (ValidationException $exception) {
            throw new PlatformException($exception->getMessage());
        }
    }

    protected function buildErrorLogData($request)
    {
        $data = [];
        $data['num'] = 1;
        $data['update_time'] = Carbon::now()->toDateTimeString();
        $data['rid'] = $request->input('cid', 0);
        $data['server_id'] = $request->input('sid', 0);
        $data['platform'] = $request->input('platform', '');
        $data['device'] = $request->input('device_type', '');
        $data['version'] = $request->input('version', '');
        $data['err_type'] = $request->input('error_type', 0);
        $data['match_id'] = $request->input('match_id', 0);
        $data['mid'] = md5($request->input('msg'));
        $data['msg'] = $request->input('msg');

        return $data;
    }
}
