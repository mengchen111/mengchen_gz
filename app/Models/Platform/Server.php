<?php

namespace App\Models\Platform;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use ServerDataMap;

    protected $connection = 'mysql-platform';
    protected $table = 'server';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $appends = [
        'server_status',
        'server_address',
        'server_type',
        'server_name',
    ];

    protected $hidden = [
        'mysql_passwd',
        'h_mysql_passwd',
    ];

    protected $guarded = [
        //
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

    public function getAreaListAttribute()
    {
        return $this->areaList;
    }

    public function getServerTypeMapAttribute()
    {
        return $this->serverTypeMap;
    }

    public function getServerStatusMapAttribute()
    {
        return $this->serverStatusMap;
    }

    public function setOpenTimeAttribute($value)
    {
        $this->attributes['open_time'] = Carbon::parse($value)->timestamp;
    }
}
