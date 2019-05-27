<?php

namespace miolae\Accounting\Interfaces\DTO;

interface AccountInterface
{
    public function getAmount(): float;

    public function getAmountHeld(): float;
}
