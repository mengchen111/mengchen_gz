<?php

namespace App\Models\Platform;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'server';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $serverStatusMap = [
        0 => '未生效',
        1 => '新服',
        2 => 'Hot',
        3 => '普通',
    ];
    protected $serverTypeMap = [
        0 => '正常',
        1 => '客户端专用审核服',
    ];

    protected $appends = [
        'server_status',
        'server_address',
        'server_type',
        'server_name',
    ];

    protected $visible = [
        'id', 'area', 'area_name', 'name', 'host', 'port', 'http_port', 'status',
        'status_msg', 'is_update', 'is_update_msg', 'open_time', 'start_time', 'rate',
        'mysql_host', 'mysql_port', 'mysql_user', 'mysql_passwd', 'mysql_data_name',
        'mysql_log_name', 'type', 'can_see', 'is_cron', 'server_status', 'server_address',
        'server_type', 'server_name',
    ];

    protected $fillable = [
    ];

    public function getServerStatusAttribute()
    {
        return $this->serverStatusMap[$this->attributes['status']];
    }

    public function getServerAddressAttribute()
    {
        return $this->attributes['host'] . ':' . $this->attributes['port'];
    }

    public function getServerTypeAttribute()
    {
        return $this->serverTypeMap[$this->attributes['type']];
    }

    public function getServerNameAttribute()
    {
        return $this->attributes['area_name'] . '-' . $this->attributes['name'];
    }

    public function getOpenTimeAttribute($value)
    {
        return Carbon::createFromTimestamp($value)->toDateTimeString();
    }
}
