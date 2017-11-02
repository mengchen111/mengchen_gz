<?php

namespace App\Exceptions;

use Exception;

class PlatformException extends Exception
{
    protected $code;

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $this->code = $code ?: config('exceptions.PlatformException');
        parent::__construct($message, $this->code, $previous);
    }
}