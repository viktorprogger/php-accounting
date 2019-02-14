<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Interfaces\Services\TransactionInterface as TransactionServiceInterface;

abstract class TransactionService implements TransactionServiceInterface
{
    public static function createNewTransaction(InvoiceInterface $invoice): TransactionInterface
    {
        $transaction = static::getTransactionInstance();
        static::setInvoice($transaction, $invoice);
        $transaction->setStateNew();

        return $transaction;
    }

    abstract protected static function getTransactionInstance(): TransactionInterface;
}
