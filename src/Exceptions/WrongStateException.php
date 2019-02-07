<?php

namespace miolae\Accounting\Exceptions;

use miolae\Accounting\Interfaces\ExceptionInterface;

class WrongStateException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message = "The given object is in a wrong state", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
