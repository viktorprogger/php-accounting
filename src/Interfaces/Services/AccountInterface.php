<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface as ModelInterface;

interface AccountInterface
{
    public function getAccount(): ModelInterface;

    public function saveModel(): void;

    public function hold(float $amount): void;

    public function withdraw(float $amount): void;

    public function add(float $amount): void;

    public function getAmount(): float;

    public function getAmountHeld(): float;

    public function getAmountAvailable(): float;

    public function isBlackHole(): bool;

    /**
     * Returns specified funds from hold
     *
     * @param float $getAmount
     */
    public function repay(float $getAmount): void;
}
