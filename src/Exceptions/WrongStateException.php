<?php

namespace miolae\Accounting\Exceptions;

class WrongStateException extends \RuntimeException
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'The given object is in a wrong state';
        }

        parent::__construct($message, $code, $previous);
    }
}
