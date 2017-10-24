<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 10/24/17
 * Time: 15:53
 */

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Npc extends Model
{
    protected $connection = 'mysql-game';
    protected $table = 'npc';
    protected $primaryKey = 'rid';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $hidden = [
    ];

    protected $guarded = [
    ];
}