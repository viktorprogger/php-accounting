<?php

namespace miolae\Accounting\Interfaces\Models;

interface AccountInterface
{
    public function getAmount(): float;

    public function getAmountHeld(): float;
}
