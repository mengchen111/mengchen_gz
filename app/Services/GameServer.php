<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 9/25/17
 * Time: 18:12
 */

namespace App\Services;

use GuzzleHttp;
use App\Exceptions\GameServerException;
use BadMethodCallException;

class GameServer
{
    public static function __callStatic($name, $arguments)
    {
        switch ($name) {
            case 'gameServerApiAddress':
                return config('custom.game_server_api_address');
                break;
            default:
                throw new BadMethodCallException('Call to undefined method ' . self::class . "::${name}()");
        }
    }

    public static function httpClient()
    {
        return new GuzzleHttp\Client([
            'base_uri' => self::gameServerApiAddress(),
            'connect_timeout' => 5,
        ]);
    }

    public static function request($method, $uri, Array $params = null)
    {
        switch ($method) {
            case 'GET':
                return self::getData($uri, $params);
                break;
            case 'POST':
                return self::postData($uri, $params);
                break;
            default:
                throw new GameServerException('method无效');
        }
    }

    protected static function getData($uri, $params = null)
    {
        try {
            $res = self::httpClient()->request('GET', $uri, [
                'query' => $params,
            ])
                ->getBody()
                ->getContents();
        } catch (\Exception $exception) {
            throw new GameServerException('调用游戏后端接口失败：' . $exception->getMessage(), $exception);
        }

        $result = self::decodeResponse($res);

        self::checkResult($result);

        return $result;
    }

    protected static function postData($uri, $params = null)
    {
        try {
            $res = self::httpClient()->request('POST', $uri, [
                'form_params' => $params,
            ])
                ->getBody()
                ->getContents();
        } catch (\Exception $exception) {
            throw new GameServerException('调用游戏后端接口失败：' . $exception->getMessage(), $exception);
        }

        $result = self::decodeResponse($res);

        self::checkResult($result);

        return $result;
    }

    protected static function checkResult($result)
    {
        if (empty($result['result'])) {     //result为0或者返回空
            throw new GameServerException('调用游戏后端接口成功，但是返回的结果错误：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    protected static function decodeResponse($res)
    {
        return json_decode($res, true);
    }
}