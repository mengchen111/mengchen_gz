<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'role';
    protected $primaryKey = 'rid';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $visible = [
        'rid', 'nickname', 'server_id', 'login_time',
    ];

    protected $fillable = [
        //
    ];
}
