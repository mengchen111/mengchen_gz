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

    protected $orderCreatorTypeMap = [
        1 => 'player',  //玩家充值
        2 => 'user',    //代理商充值
    ];

    protected $tradeTypes = [       //可选的trade_type
        'NATIVE',   //扫码支付
        'APP',      //app支付
    ];
}