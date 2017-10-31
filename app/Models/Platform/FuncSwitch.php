<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class FuncSwitch extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'func_switch';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $visible = [
        'func_mark', 'func_status'
    ];

    protected $fillable = [
        //
    ];
}
