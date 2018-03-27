<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class FuncSwitch extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'func_switch';
    protected $primaryKey = 'id';
    public $timestamps = false;     //不使用ORM的时间格式化功能（更新数据时也会更改时间格式）
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $visible = [
//        'func_mark', 'func_status'
    ];

    protected $fillable = [
        'ver_switch','area','func_mark','platform','func_name','func_status','device_type','client_version'
    ];

    public $funcMarks = [
        'ZT_ZTDDZ' => '昭通_昭通斗地主',
        'ZT_ZTMJ' => '昭通_昭通麻将',
        'ZT_ZXMJ' => '昭通_镇雄麻将',
        'ZT_ZTMENJI' => '昭通_昭通闷鸡',
        'ZT_ZTDZ' => '昭通_德州',
        'ZT_HD' => '昭通_活动',
        'ZT_WP' => '昭通_物品',
        'ZT_SC' => '昭通_商城',
        'ZT_DH' => '昭通_兑换',
        'ZT_PHB' => '昭通_排行榜',
        'ZT_RW' => '昭通_任务',
        'ZT_TJYJ' => '昭通_推荐有奖',
        'ZT_ZJ' => '昭通_战绩',
        'ZT_GD' => '昭通_更多',
        'ZT_YJ' => '昭通_邮件',
        'ZT_ZTNN' => '昭通_昭通牛牛',
        'DSFCZ' => '第三方充值方式',
        'SMRZ' => '实名认证',
        'WX' => '微信有关所有功能',
        'HYF' => '好友房',
        'CZ' =>  '充值有关所有功能',
    ];

    //游戏地区
    public $areaList = [
        'default'  => 'default-默认',
        'zhaotong'  => 'zhaotong-昭通',
    ];
}
