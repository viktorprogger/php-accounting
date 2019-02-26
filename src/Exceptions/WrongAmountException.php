<?php

namespace miolae\Accounting\Exceptions;

use miolae\Accounting\Interfaces\ExceptionInterface;

class WrongAmountException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($amount, int $code = 0, \Throwable $previous = null)
    {
        $message = sprintf('Funds amount must be a positive number, "%s" given', $amount);
        parent::__construct($message, $code, $previous);
    }
}
