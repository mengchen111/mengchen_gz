<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class WhiteList extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'white_list';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $hidden = [
        //
    ];

    protected $fillable = [
        //
    ];
}
