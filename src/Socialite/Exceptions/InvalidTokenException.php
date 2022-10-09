<?php

namespace Pgyf\Opensdk\Kernel\Socialite\Exceptions;

class InvalidTokenException extends Exception
{
    /**
     * @var string
     */
    public $token;

    public function __construct(string $message, string $token)
    {
        parent::__construct($message, -1);

        $this->token = $token;
    }
}
