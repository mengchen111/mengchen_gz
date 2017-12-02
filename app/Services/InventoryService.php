<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\User;

class InventoryService
{
    protected static $itemIdMap = [     //后台与游戏后端的道具id的映射关系
        1 => 1030005,                   //后台房卡id 1对应游戏数据库房卡id 1030005
        2 => 1,
    ];

    public static function addStock($recipientType, $recipientId, $itemType, $amount)
    {
        switch ($recipientType) {
            case 'agent':
                self::addStock4Agent($recipientId, $itemType, $amount);
                break;
            case 'player':
                self::addStock4Player($recipientId, $itemType, $amount);
                break;
            default:
                break;
        }
    }

    public static function addStock4Agent($agentId, $itemType, $amount)
    {
        $agent = User::with(['inventory' => function ($query) use ($itemType) {
            $query->where('item_id', $itemType);
        }])->find($agentId);

        if (empty($agent->inventory)) {
            $agent->inventory()->create([
                'user_id' => $agent->id,
                'item_id' => $itemType,
                'stock' => $amount,
            ]);
        } else {
            $agent->inventory->stock += $amount;
            $agent->inventory->save();
        }

        return true;
    }

    public static function addStock4Player($playerId, $itemType, $amount)
    {

    }
}