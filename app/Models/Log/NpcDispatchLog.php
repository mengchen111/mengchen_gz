<?php

namespace App\Models\Log;

use App\Models\Game\NpcDataMap;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NpcDispatchLog extends Model
{
    use NpcDataMap;

    protected $connection = 'mysql-log';
    protected $table = 'npc_dispatch_log';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $hidden = [
    ];

    protected $fillable = [
    ];

    protected $appends = [
        'start_vs_end_date', 'start_vs_end_time',
    ];

    public function getCreateTimeAttribute($value)
    {
        return Carbon::createFromTimestamp($value)->format($this->dateFormat);
    }

    public function getDoStartDateAttribute($value)
    {
        return Carbon::createFromTimestamp($value)->format('Y-m-d');
    }

    public function getDoEndDateAttribute($value)
    {
        return Carbon::createFromTimestamp($value)->format('Y-m-d');
    }

    public function getDoStartTimeAttribute($value)
    {
        return Carbon::createFromTimestamp(Carbon::today()->timestamp + $value)->toTimeString();
    }

    public function getDoEndTimeAttribute($value)
    {
        return Carbon::createFromTimestamp(Carbon::today()->timestamp + $value)->toTimeString();
    }

    public function getStartVsEndDateAttribute()
    {
        return $this->do_start_date . '/' . $this->do_end_date;
    }

    public function getStartVsEndTimeAttribute()
    {
        if ($this->is_all_day) {
            return '全天';
        }
        return $this->do_start_time . '/' . $this->do_end_time;
    }

    public function getGameTypeAttribute($value)
    {
        return array_key_exists($value, $this->gameTypeMap)
            ? $this->gameTypeMap[$value]
            : '未定义的游戏类型';
    }

    public function getRoomTypeAttribute($value)
    {
        return array_key_exists($value, $this->roomTypeMap)
            ? $this->roomTypeMap[$value]
            : '未定义的房间类型';
    }

    public function getIsOpenAttribute($value)
    {
        return $value ? '开启' : '关闭';    //0关闭，1开启
    }
}
