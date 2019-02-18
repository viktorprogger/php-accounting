<?php

namespace miolae\Accounting\Decorators;

use miolae\Accounting\Interfaces\Decorators\InvoiceDecoratorInterface;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Traits\ModelMixinTrait;

/**
 * Class InvoiceDecorator
 *
 * @package miolae\Accounting\Decorators
 *
 * @mixin InvoiceInterface
 */
abstract class InvoiceDecorator implements InvoiceDecoratorInterface
{
    use ModelMixinTrait;

    /** @var InvoiceInterface */
    protected $model;

    public function __construct(InvoiceInterface $invoice)
    {
        $this->model = $invoice;
    }

    public function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): void
    {
        $this->setAccountFrom($accountFrom);
        $this->setAccountTo($accountTo);
        $this->model->setAmount($amount);
    }

    /**
     * @return InvoiceInterface
     */
    public function getModel(): InvoiceInterface
    {
        return $this->model;
    }

    /**
     * Check if associated invoice model can be canceled
     *
     * @return bool
     */
    public function canCancel(): bool
    {
        return $this->model->isStateHold() || $this->model->isStateCreated() || $this->model->isStateTransacted();
    }

    public function canHold(): bool
    {
        return $this->model->isStateCreated();
    }

    public function canUnhold(): bool
    {
        return $this->model->isStateTransacted();
    }
}
