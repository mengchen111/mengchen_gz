<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 10/24/17
 * Time: 16:40
 * npc表的字段的映射关系
 */

namespace App\Models\Game;

trait NpcDataMap
{
    protected $gameTypeMap = [
        0 => '未分配',
        1 => '闷鸡',
        2 => '斗地主',
        3 => '昭通麻将',
        4 => '镇雄麻将',
        5 => '牛牛',
        6 => '南充斗地主',
        7 => '南充麻将',
        8 => '成都麻将',
        9 => '德州',
        14 => '红中麻将',
        15 => '跑胡子',
        16 => '牛牛',
        17 => '十三水',
    ];

    protected $roomTypeMap = [
        0 => '未分配',
        1 => '房间类型1',
        2 => '房间类型2',
        3 => '房间类型3',
        4 => '房间类型4',
    ];

    protected $statusMap = [
        0 => '待准备',
        1 => '游戏中',
        2 => '空闲',
        3 => '等待上线',
    ];

    protected $dispatchRoomTypeMap = [  //ai调度列表的房间映射关系表(暂未使用，原代码显示和编辑的映射关系不一致)
        1 => '低级房',
        2 => '中级房',
        3 => '高级房',
    ];
}