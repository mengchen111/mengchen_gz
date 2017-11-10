<?php

namespace App\Http\Controllers\Platform;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Exceptions\PlatformException;
use App\Models\OperationLogs;

class HeadimgController extends Controller
{
    protected $disk = 'headimg';

    public function upload(Request $request)
    {
        $this->filterUploadFrom($request);
        $fileName = $request->input('img_name') . '.' . $request->file('img')->extension();;
        $res = Storage::disk($this->disk)->putFileAs('', $request->file('img'), $fileName);

        OperationLogs::add(1, $request->path(), $request->method(),
            '[platform]上传头像', $request->header('User-Agent'), json_encode($request->all()));

        return [
            'errno' => (int) 1,
        ];
    }

    protected function filterUploadFrom($request)
    {
        try {
            $this->validate($request, [
                'img_name' => 'required',
                'img' => 'required|mimes:jpeg,jpg,bmp,png,gif|max:5120',
            ]);
        } catch (ValidationException $exception) {
            throw new PlatformException($exception->getMessage());
        }
    }
}
