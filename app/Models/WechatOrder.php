<?php

namespace App\Models;

use App\Traits\WeChatPaymentTrait;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WechatOrder extends Model
{
    use WeChatPaymentTrait;

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
        //'order_qr_code',
        'order_creator_name',
        'order_creator_type_name',
        'item_type_name',
        'item_delivery_status_name',
        'trade_type_name',
        'order_status_name',
        'total_fee_yuan',
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

    public function getOrderCreatorNameAttribute()
    {
        if ((int) $this->attributes['order_creator_type'] === 1) {  //玩家
            return $this->attributes['order_creator_id'];
        }
        if ((int) $this->attributes['order_creator_type'] === 2) {  //代理商
            $agent = User::find($this->attributes['order_creator_id']);
            if (empty($agent)) {
                return '';
            }
            return $agent->account;
        }
    }

    public function getOrderCreatorTypeNameAttribute()
    {
        if ((int) $this->attributes['order_creator_type'] === 1) {
            return '玩家';
        }
        if ((int) $this->attributes['order_creator_type'] === 2) {
            return '代理商';
        }
        return $this->attributes['order_creator_type'];
    }

    public function getItemTypeNameAttribute()
    {
        $item = ItemType::find($this->attributes['item_type_id']);
        return $item->name;
    }

    public function getItemDeliveryStatusNameAttribute()
    {
        return $this->itemDeliveryStatusMap[$this->attributes['item_delivery_status']];
    }

    public function getTradeTypeNameAttribute()
    {
        return $this->tradeTypeMap[$this->attributes['trade_type']];
    }

    public function getOrderStatusNameAttribute()
    {
        return $this->orderStatusMap[$this->attributes['order_status']];
    }

    public function getTotalFeeYuanAttribute()
    {
        return $this->attributes['total_fee'] / 100;
    }
}
