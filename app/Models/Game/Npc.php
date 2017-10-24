<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 10/24/17
 * Time: 15:53
 */

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Npc extends Model
{
    use NpcDataMap;     //字段值的映射关系

    protected $connection = 'mysql-game';
    protected $table = 'npc';
    protected $primaryKey = 'rid';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $visible = [
        'rid', 'nick', 'exp', 'diamond', 'crystal', 'create_time', 'status',
        'game_type', 'room_type'
    ];

    protected $fillable = [
    ];

    public function getStatusAttribute($value)
    {
        return $this->statusMap[$value];
    }

    public function getCreateTimeAttribute($value)
    {
        return Carbon::createFromTimestamp($value)->format($this->dateFormat);
    }

    public function getGameTypeAttribute($value)
    {
        return $this->gameTypeMap[$value];
    }

    public function getRoomTypeAttribute($value)
    {
        return $this->roomTypeMap[$value];
    }

    //获取调用天数
    public function getDuration()
    {
        return ($this->do_end_date - $this->do_start_date) / 24 * 60 * 60;
    }
}