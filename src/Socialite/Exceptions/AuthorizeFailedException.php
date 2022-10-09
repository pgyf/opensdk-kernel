<?php

namespace Pgyf\Opensdk\Kernel\Socialite\Exceptions;


class AuthorizeFailedException extends Exception
{
    /**
     * @var array
     */
    public $body;

    /**
     * @param string $message
     * @param mixed $body
     */
    public function __construct(string $message, $body)
    {
        parent::__construct($message, -1);

        $this->body = (array) $body;
    }
}
