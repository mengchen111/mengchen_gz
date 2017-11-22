<?php

namespace App\Models\Platform;

trait ServerDataMap
{
    public $serverStatusMap = [
        0 => '未生效',
        1 => '新服',
        2 => 'Hot',
        3 => '普通',
    ];
    public $serverTypeMap = [
        0 => '正常',
        1 => 'IOS审核服',
        2 => 'Android审核服',
        3 => 'Window审核服',
    ];
    public $areaList = [
        'default'  => 'default-默认',
        'zhaotong'  => 'zhaotong-昭通',
    ];
    public $yesOrNoMap = [
        '否',
        '是',
    ];
}