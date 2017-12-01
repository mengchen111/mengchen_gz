<?php

namespace App\Models;

trait WechatOrderMap
{
    protected $tradeTypeMap = [
        'QRCode' => 'NATIVE',
        'app' => 'APP',
    ];
}