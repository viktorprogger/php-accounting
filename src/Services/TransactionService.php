<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Decorators\TransactionServiceInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;

/**
 * Class TransactionService
 *
 * @package miolae\Accounting\Services
 */
abstract class TransactionService implements TransactionServiceInterface
{
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
