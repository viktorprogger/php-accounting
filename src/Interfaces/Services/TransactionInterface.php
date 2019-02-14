<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface as TransactionModel;

interface TransactionInterface
{
    public static function createNewTransaction(InvoiceInterface $invoice): TransactionModel;

    public static function saveModel(TransactionModel $transaction): TransactionModel;

    public static function setInvoice(TransactionModel $transaction, InvoiceInterface $invoice): TransactionModel;
}
