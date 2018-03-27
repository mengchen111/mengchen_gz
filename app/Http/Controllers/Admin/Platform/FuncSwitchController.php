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
            $func_mark = explode(',', $item);
            $mark_name = '';
            foreach ($func_mark as $v) {
                if (isset($func_marks[$v])) {
                    $mark_name .= $v . '-' . $func_marks[$v] . '|';
                }
            }
            $item['mark_name'] = rtrim($mark_name, '|');
        }
        return $result;
    }

    /**
     * 功能开关表单信息
     * @param AdminRequest $request
     * @param mixed $id
     * @return mixed
     */
    public function formInfo(AdminRequest $request, $id = '')
    {
        $funcSwitch = app(FuncSwitch::class);
        $data['func_marks'] = $funcSwitch->funcMarks;
        $data['area_list'] = $funcSwitch->areaList;
        //渠道
        $platform = Platform::all();
        $data['platform_list'] = $platform;
        if (!empty($id)) {
            $result = $funcSwitch->findOrFail($id);
            $result['func_mark'] = explode(',', $result['func_mark']);
            $data['data'] = $result;
        }
        return $data;
    }

    /**
     * 功能开关添加
     * @param AdminRequest $request
     * @return array
     */
    public function store(AdminRequest $request)
    {
        $this->addLog('添加功能开关控制');

        $data = $request->all();
        $func_mark = $data['func_mark'];
        if (!is_array($func_mark)) {
            $func_mark = [$func_mark];
        }
        $data['func_mark'] = implode(',', $func_mark);
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
        $this->addLog('修改功能开关控制');

        $data = $request->all();
        $func_mark = $data['func_mark'];
        if (!is_array($func_mark)) {
            $func_mark = [$func_mark];
        }
        $data['func_mark'] = implode(',', $func_mark);
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
