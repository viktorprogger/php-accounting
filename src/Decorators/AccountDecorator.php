<?php

namespace miolae\Accounting\Decorators;

use miolae\Accounting\Exceptions\OutOfFundsException;
use miolae\Accounting\Interfaces\Decorators\AccountDecoratorInterface;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Traits\ModelMixinTrait;

/**
 * Class AccountDecorator
 *
 * @package miolae\Accounting\Decorators
 *
 * @mixin AccountInterface
 */
abstract class AccountDecorator implements AccountDecoratorInterface
{
    use ModelMixinTrait;

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
