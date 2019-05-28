<?php

namespace viktorprogger\Accounting\Exceptions;

use viktorprogger\Accounting\Interfaces\ExceptionInterface;

class OutOfFundsException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message = "Not enough funds available", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
