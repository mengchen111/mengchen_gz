<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'server';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $visible = [
        'id', 'area', 'area_name', 'name', 'host', 'port', 'http_port', 'status',
        'status_msg', 'is_update', 'is_update_msg', 'open_time', 'start_time',
        'mysql_host', 'mysql_port', 'mysql_user', 'mysql_passwd', 'mysql_data_name',
        'mysql_log_name', 'type', 'can_see'
    ];

    protected $fillable = [
    ];
}
