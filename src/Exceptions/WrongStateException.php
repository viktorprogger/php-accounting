<?php

namespace miolae\Accounting\Exceptions;

class WrongStateException extends Exception
{
    public function __construct(string $message = "The given object is in a wrong state", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
