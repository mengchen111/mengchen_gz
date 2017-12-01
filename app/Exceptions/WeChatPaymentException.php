<?php
/**
 * Created by PhpStorm.
 * User: liudian
 * Date: 9/7/17
 * Time: 10:58
 */

namespace App\Exceptions;

use Exception;

class WeChatPaymentException extends Exception
{
    protected $code;

    /**
     * 微信支付异常类
     * @param string $message
     */
    public function __construct($message = '', Exception $previous = null)
    {
        $this->code = config('exceptions.WeChatPaymentException');
        parent::__construct($message, $this->code, $previous);
    }
}