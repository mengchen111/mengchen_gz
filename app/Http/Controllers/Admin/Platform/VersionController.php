<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Models\Platform\ClientVersion;
use App\Models\Platform\FuncSwitch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VersionController extends Controller
{
    // 版本模式控制
    public function showFuncSwitchVersion(Request $request)
    {
        $this->validate($request, [
            'area' => 'string',
            'platform' => 'string',
            'device_type' => 'string',
            'ver_id' => 'string',
        ]);

        $result['code'] = 0;
        $result['area'] = $request->area;
        $result['platform'] = $request->platform;
        $result['device_type'] = $request->device_type;
        $result['ver_id'] = $request->ver_id;

        // 版本抓转换信息
        $versionSwitchList = ClientVersion::where('area', $request->area)
            ->where('platform', $request->platform)
            ->where('device_type', $request->device_type)
            ->where('ver_id', $request->ver_id)
            ->first();

        if (! empty($versionSwitchList)) {
            $result['ver_switch'] = $versionSwitchList->ver_switch;
            $result['status'] = $versionSwitchList->status;

            // 查询功能开关信息
            $funcSwitchList = FuncSwitch::where('area', $request->area)
                ->where('platform', $request->platform)
                ->where('device_type', $request->device_type)
                ->where('ver_switch', $versionSwitchList->ver_switch)
                ->where('client_version', $request->ver_id)
                ->get()
                ->toArray();
        } else {
            $funcSwitchList = FuncSwitch::where('area', $request->area)
                ->where('platform', $request->platform)
                ->where('device_type', $request->device_type)
                ->get()
                ->toArray();
        }

        $result['func_switch_list'] = $funcSwitchList;
        $result['code'] = 0;

        return $result;
    }
}
