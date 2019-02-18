<?php

namespace miolae\Accounting\Interfaces\Decorators;

use miolae\Accounting\Interfaces\Models\AccountInterface;

/**
 * Interface AccountDecoratorInterface
 *
 * @package miolae\Accounting\Interfaces\Decorators
 *
 * @mixin AccountInterface
 */
interface AccountDecoratorInterface
{
    public function getModel(): AccountInterface;

    public function saveModel(): void;

    /**
     * Hold the given amount of funds
     *
     * @param float $amount
     */
    public function hold(float $amount): void;

    /**
     * Reduce funds by the specified amount.
     *
     * @param float $amount
     */
    public function withdraw(float $amount): void;

    /**
     * Increase funds by the specified amount
     *
     * @param float $amount
     */
    public function add(float $amount): void;

    public function getAmountAvailable(): float;

    public function isBlackHole(): bool;

    /**
     * Returns specified funds from hold
     *
     * @param float $getAmount
     */
    public function repay(float $getAmount): void;
}
