<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 10/24/17
 * Time: 14:34
 */

namespace App\Services\Platform;

use Exception;

class EncryptionService
{
    protected static $privateKey = __DIR__ . '/rsa_private_key.pem';
    protected static $publicKey = __DIR__ . '/rsa_public_key.pem';

    protected static function getPrivateKey()
    {
        return openssl_pkey_get_private(file_get_contents(self::$privateKey));
    }

    protected static function getPublicKey()
    {
        return openssl_pkey_get_public(file_get_contents(self::$publicKey));
    }

    // 私钥加密
    public static function privateEncrypt($needEncryptData) {
        $encryptedData = '';
        $result = openssl_private_encrypt($needEncryptData, $encryptedData, self::getPrivateKey());
        if ($result) {
            return $encryptedData;
        }
        throw new Exception('私钥加密失败');
    }

    // 公钥解密
    public static function publicDecrypt($encryptedData) {
        $decryptedData = '';
        $result = openssl_public_decrypt($encryptedData, $decryptedData, self::getPublicKey());
        if ($result) {
            return $decryptedData;
        }
        throw new Exception('公钥解密失败');
    }

    // 公钥加密
    public static function publicEncrypt($needEncryptData) {
        $encryptedData = '';
        $result = openssl_public_encrypt($needEncryptData, $encryptedData, self::getPublicKey());
        if ($result) {
            return $encryptedData;
        }
        throw new Exception('公钥加密失败');
    }

    // 私钥解密
    public static function privateDecrypt($encryptedData) {
        $decryptedData = '';
        $result = openssl_private_decrypt($encryptedData, $decryptedData, self::getPrivateKey());
        if ($result) {
            return $decryptedData;
        }
        throw new Exception('私钥解密失败');
    }
}