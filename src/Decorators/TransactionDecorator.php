<?php

namespace miolae\Accounting\Decorators;

use miolae\Accounting\Interfaces\Decorators\TransactionDecoratorInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Traits\ModelMixinTrait;

/**
 * Class TransactionDecorator
 *
 * @package miolae\Accounting\Decorators
 *
 * @mixin TransactionInterface
 */
abstract class TransactionDecorator implements TransactionDecoratorInterface
{
    use ModelMixinTrait;

    /** @var TransactionInterface */
    protected $model;

    public function createNewTransaction(InvoiceInterface $invoice, $stateTo): void
    {
        $this->setInvoice($invoice);
        $this->model->setStateNew();
        $this->model->setInvoiceStateFrom($invoice->getState());
        $this->model->setInvoiceStateTo($stateTo);
    }

    /**
     * @return TransactionInterface
     */
    public function getModel(): TransactionInterface
    {
        return $this->model;
    }
}
