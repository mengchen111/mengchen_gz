<?php

namespace App\Traits;

trait WeChatPaymentTrait
{
    protected $orderStatusMap = [
        1 => '内部订单创建成功',
        2 => '预支付订单创建成功',
        3 => '预支付订单创建失败',
        4 => '支付成功',
        5 => '支付失败',
        6 => '已关闭',
    ];
}