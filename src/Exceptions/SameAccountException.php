<?php

namespace miolae\Accounting\Exceptions;

use miolae\Accounting\Interfaces\ExceptionInterface;

class SameAccountException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message = "Trying to transact funds from an account to itself", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
