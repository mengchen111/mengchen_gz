<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

    protected $appends = [
        'order_qr_code',
    ];

    public function isPaid()
    {
        return (int) $this->order_status === $this->orderPayedStatusId;
    }

    public function getOrderQrCodeAttribute()
    {
        $content = $this->attributes['code_url'];
        if (empty($content)) {
            return null;
        }
        return base64_encode(QrCode::format('png')->size(200)->generate($content));
    }
}
