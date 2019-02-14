<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Exceptions\OutOfFundsException;
use miolae\Accounting\Interfaces\Models\AccountInterface as ModelInterface;
use miolae\Accounting\Interfaces\Services\AccountInterface;

abstract class AccountService implements AccountInterface
{
    /**
     * @param ModelInterface $account
     * @param float          $amount
     */
    public static function hold(ModelInterface $account, float $amount): void
    {
        if (!static::isBlackHole($account) && static::getAmountAvailable($account) < $amount) {
            throw new OutOfFundsException();
        }

        static::holdInternal($account, $amount);
    }

    public static function getAmountAvailable(ModelInterface $account): float
    {
        return $account->getAmount() - $account->getAmountHeld();
    }

    abstract protected static function holdInternal(ModelInterface $account, float $amount): void;
}
