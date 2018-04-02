<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\OperationLogs;
use App\Models\Platform\FuncSwitch;
use App\Models\Platform\Platform;
use GuzzleHttp\Psr7\Request;

class FuncSwitchController extends Controller
{
    public $deviceType = [
        'android' => 'android-安卓',
        'ios' => 'ios-苹果',
        'windows' => 'windows-微软window',
    ];
    public $verSwitch = [
        1 => '平台版',
        2 => '单独版',
    ];
    public $funcStatus = [
        1 => '开启',
        2 => '关闭',
    ];

    /**
     * 功能开关列表
     * @param AdminRequest $request
     * @return mixed
     */
    public function index(AdminRequest $request)
    {
        $this->addLog('查看功能开关控制列表');
        //搜索
        $t_type = $request->get('t_type', '');
        $t_val = trim($request->get('t_val', ''));
        $model = app(FuncSwitch::class);
        $func_marks = $model->funcMarks;

        //地区
        if ($t_type && $t_val) {
            switch ($t_type) {
                case '1':
                    $model = $model->where('area', $t_val);
                    break;
                case '2':
                    $model = $model->where('platform', $t_val);
                    break;
                case '3':
                    $model = $model->where('func_mark', $t_val);
                    break;
                default:
                    break;
            }
        }
        //
        $ver_switch = $request->get('ver_switch', '');
        if ($ver_switch) {
            $model = $model->where('ver_switch', $ver_switch);
        }

        $result = $model->paginate($this->per_page);
        foreach ($result as $item) {
            $func_mark = explode(',', $item['func_mark']);
            $mark_name = '';
            foreach ($func_mark as $v) {
                if (isset($func_marks[$v])) {
                    $mark_name .= $func_marks[$v] . '|';
                }
            }
            $item['mark_name'] = rtrim($mark_name, '|');
        }
        return $result;
    }

    /**
     * 功能开关表单信息
     * @param AdminRequest $request
     * @return mixed
     */
    public function formInfo(AdminRequest $request)
    {
        $funcSwitch = app(FuncSwitch::class);
        $data['func_marks'] = $funcSwitch->funcMarks;
        $data['area_list'] = $funcSwitch->areaList;
        //渠道
        $platform = Platform::all()->pluck('name', 'flag');
        foreach ($platform as $key => $val) {
            $data['platform_list'][$key] = $key . '-' . $val;
        }
        $data['device_type'] = $this->deviceType;
        $data['ver_switch'] = $this->verSwitch;
        $data['func_status'] = $this->funcStatus;
        return $data;
    }

    /**
     * 功能开关添加
     * @param AdminRequest $request
     * @return array
     */
    public function store(AdminRequest $request)
    {
        $this->validator($request);
        $this->addLog('添加功能开关控制');

        $data = $this->buildData($request);
        $result = FuncSwitch::create($data);
        return [
            'message' => '添加功能开关' . $result ? '成功' : '失败',
        ];
    }

    /**
     * 编辑功能开关
     * @param AdminRequest $request
     * @param FuncSwitch $func
     * @return array
     */
    public function update(AdminRequest $request, FuncSwitch $func)
    {
        $this->validator($request);
        $this->addLog('修改功能开关控制');

        $data = $this->buildData($request);
        $result = $func->update($data);
        return [
            'message' => '编辑功能开关' . $result ? '成功' : '失败',
        ];
    }

    /**
     * 删除功能开关
     * @param AdminRequest $request
     * @param FuncSwitch $func
     * @return array
     */
    public function destroy(AdminRequest $request, FuncSwitch $func)
    {
        $this->addLog('删除功能开关控制');

        $result = $func->delete();
        return [
            'message' => '删除功能开关' . $result ? '成功' : '失败',
        ];
    }

    protected function buildData($request)
    {
        $item = $this->formInfo($request);
        $data = $request->only(['ver_switch', 'area', 'func_mark', 'platform', 'func_name', 'func_status', 'device_type', 'client_version']);
        if (isset($data['area'])) {
            $data['area'] = array_search($data['area'], $item['area_list']);
        }
        if (isset($data['device_type'])) {
            $data['device_type'] = array_search($data['device_type'], $item['device_type']);
        }
        if (isset($data['func_mark'])) {
            $funcMarks = [];
            foreach ($data['func_mark'] as $key => $mark) {
                $funcMarks[] = array_search($mark, $item['func_marks']);
            }
            $data['func_mark'] = implode(',', $funcMarks);
        }
        if (isset($data['func_status'])) {
            $data['func_status'] = array_search($data['func_status'], $item['func_status']);
        }
        if (isset($data['platform'])) {
            $data['platform'] = array_search($data['platform'], $item['platform_list']);
        }
        if (isset($data['ver_switch'])) {
            $data['ver_switch'] = array_search($data['ver_switch'], $item['ver_switch']);
        }
        return $data;
    }

    public function validator(AdminRequest $request)
    {
        $this->validate($request, [
            'ver_switch' => 'required',
            'area' => 'required',
            'platform' => 'required',
            'func_status' => 'required',
            'device_type' => 'required',
            'client_version' => 'required'
        ]);
    }

    /**
     * 添加操作日志
     * @param string $message
     */
    public function addLog($message = '')
    {
        OperationLogs::add(request()->user()->id, request()->path(), request()->method(),
            $message, request()->header('User-Agent'), json_encode(request()->all()));
    }


}
