<?php

namespace viktorprogger\Accounting\Interfaces\DTO;

interface AccountInterface
{
    public function getAmount(): float;

    public function getAmountHeld(): float;
}
