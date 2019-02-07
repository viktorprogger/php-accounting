<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Exceptions\OutOfFundsException;
use miolae\Accounting\Interfaces\Models\AccountInterface as ModelInterface;
use miolae\Accounting\Interfaces\Services\AccountInterface;

abstract class AccountService implements AccountInterface
{
    /** @var ModelInterface */
    protected $account;

    public function __construct(ModelInterface $account)
    {
        $this->account = $account;
    }

    public function getAccount(): ModelInterface
    {
        return $this->account;
    }

    /**
     * @param float $amount
     *
     * @throws OutOfFundsException
     */
    public function hold(float $amount): void
    {
        if (!$this->isBlackHole() && $this->getAmountAvailable() < $amount) {
            throw new OutOfFundsException();
        }

        $this->holdInternal($amount);
    }

    public function getAmountAvailable(): float
    {
        return $this->getAmount() - $this->getAmountHeld();
    }

    abstract protected function holdInternal(float $amount): void;
}
