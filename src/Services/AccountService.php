<?php

namespace viktorprogger\Accounting\Services;

use viktorprogger\Accounting\Exceptions\OutOfFundsException;
use viktorprogger\Accounting\Interfaces\Services\AccountServiceInterface;
use viktorprogger\Accounting\Interfaces\DTO\AccountInterface;

/**
 * Class AccountService
 *
 * @package viktorprogger\Accounting\Services
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
