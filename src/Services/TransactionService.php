<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Interfaces\Services\TransactionInterface as TransactionServiceInterface;

abstract class TransactionService implements TransactionServiceInterface
{
    /** @var TransactionInterface */
    protected $transaction;

    public function createNewTransaction(InvoiceInterface $invoice): void
    {
        $this->setInvoice($invoice);
        $this->setStateNew();
    }
}
