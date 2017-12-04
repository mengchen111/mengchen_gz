<?php

namespace App\Services;

use App\Exceptions\InventoryServiceException;
use App\Models\User;

class InventoryService
{
    protected static $playerAddItemApi = 'role/addItem';
    protected static $playerSubItemApi = 'role/subItem';

    protected static $itemIdMap = [     //后台与游戏后端的道具id的映射关系
        1 => 1030005,                   //后台房卡id 1对应游戏数据库房卡id 1030005
        2 => 1,
    ];

    public static function addStock($recipientType, $recipientId, $itemType, $amount)
    {
        switch ($recipientType) {
            case 'user':
                self::addStock4User($recipientId, $itemType, $amount);
                break;
            case 'player':
                self::addStock4Player($recipientId, $itemType, $amount);
                break;
            default:
                break;
        }
    }

    public static function subStock($recipientType, $recipientId, $itemType, $amount)
    {
        switch ($recipientType) {
            case 'user':
                self::subStock4User($recipientId, $itemType, $amount);
                break;
            case 'player':
                self::subStock4Player($recipientId, $itemType, $amount);
                break;
            default:
                break;
        }
    }

    public static function addStock4User($userId, $itemType, $amount)
    {
        $user = User::with(['inventory' => function ($query) use ($itemType) {
            $query->where('item_id', $itemType);
        }])->find($userId);

        self::checkUserExists($user);

        if (empty($user->inventory)) {
            $user->inventory()->create([
                'user_id' => $user->id,
                'item_id' => $itemType,
                'stock' => $amount,
            ]);
        } else {
            $user->inventory->stock += $amount;
            $user->inventory->save();
        }
    }

    protected static function checkUserExists($user)
    {
        if (empty($user)) {
            throw new InventoryServiceException('用户不存在');
        }
    }

    public static function addStock4Player($playerId, $itemType, $amount)
    {
        GameServer::request('POST', self::$playerAddItemApi, [
            'playerid' => $playerId,
            'id' => self::$itemIdMap[$itemType],
            'count' => $amount,
        ]);
    }

    public static function subStock4User($userId, $itemType, $amount)
    {
        $user = User::with(['inventory' => function ($query) use ($itemType) {
            $query->where('item_id', $itemType);
        }])->find($userId);

        self::checkUserExists($user);

        $user->inventory->stock -= $amount;
        $user->inventory->save();
    }

    public static function subStock4Player($playerId, $itemType, $amount)
    {
        GameServer::request('POST', self::$playerSubItemApi, [
            'playerid' => $playerId,
            'id' => self::$itemIdMap[$itemType],
            'count' => $amount,
        ]);
    }
}