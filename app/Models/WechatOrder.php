<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WechatOrder extends Model
{
    protected $table = 'wechat_order';
    protected $primaryKey = 'id';

    protected $hidden = [
        //
    ];

    protected $guarded = [
        //
    ];
}
