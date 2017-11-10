<?php

namespace App\Http\Controllers\Platform;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Exceptions\PlatformException;

class HeadimgController extends Controller
{
    protected $disk = 'headimg';

    public function upload(Request $request)
    {
        $this->filterUploadFrom($request);
        $fileName = $request->input('rid') . '.' . $request->file('img')->extension();;
        $res = Storage::disk($this->disk)->putFileAs('', $request->file('img'), $fileName);

        return [
            'code' => 0
        ];
    }

    protected function filterUploadFrom($request)
    {
        try {
            $this->validate($request, [
                'rid' => 'required|integer',
                'img' => 'required|mimes:jpeg,jpg,bmp,png,gif|max:5120',
            ]);
        } catch (ValidationException $exception) {
            throw new PlatformException($exception->getMessage());
        }
    }
}
