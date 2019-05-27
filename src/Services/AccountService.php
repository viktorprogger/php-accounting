<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Exceptions\OutOfFundsException;
use miolae\Accounting\Interfaces\Services\AccountServiceInterface;
use miolae\Accounting\Interfaces\DTO\AccountInterface;

/**
 * Class AccountService
 *
 * @package miolae\Accounting\Services
 */
abstract class AccountService implements AccountServiceInterface
{
    /** @var AccountInterface */
    protected $model;

    public function __construct(AccountInterface $account)
    {
        $this->model = $account;
    }

    public function getModel(): AccountInterface
    {
        return $this->model;
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
        return $this->model->getAmount() - $this->model->getAmountHeld();
    }

    abstract protected function holdInternal(float $amount): void;
}
