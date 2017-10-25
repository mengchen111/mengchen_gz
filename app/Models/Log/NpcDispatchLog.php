<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class NpcDispatchLog extends Model
{
    protected $connection = 'mysql-log';
    protected $table = 'npc_dispatch_log';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $hidden = [
    ];

    protected $fillable = [
    ];
}
