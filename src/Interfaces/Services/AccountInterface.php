<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface as ModelInterface;

interface AccountInterface
{
    public static function saveModel(ModelInterface $account): ModelInterface;

    public static function hold(ModelInterface $account, float $amount): void;

    public static function withdraw(ModelInterface $account, float $amount): void;

    public static function add(ModelInterface $account, float $amount): void;

    public static function getAmountAvailable(ModelInterface $account): float;

    public static function isBlackHole(ModelInterface $account): bool;

    /**
     * Returns specified funds from hold
     *
     * @param float          $getAmount
     * @param ModelInterface $account
     */
    public static function repay(ModelInterface $account, float $getAmount): void;
}
