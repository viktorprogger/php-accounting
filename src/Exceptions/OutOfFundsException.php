<?php

namespace miolae\Accounting\Exceptions;

class OutOfFundsException extends Exception
{
    public function __construct(string $message = "Not enough funds available", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
