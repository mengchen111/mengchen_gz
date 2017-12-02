<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WechatOrder extends Model
{
    protected $table = 'wechat_order';
    protected $primaryKey = 'id';

    protected $orderPayedStatusId = 4;

    protected $hidden = [
        //
    ];

    protected $guarded = [
        //
    ];

    public function isPaid()
    {
        return (int) $this->order_status === $this->orderPayedStatusId;
    }
}
