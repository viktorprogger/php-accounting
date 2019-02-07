<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface as TransactionModel;

interface TransactionInterface
{
    public function __construct(TransactionModel $transaction);

    public function createNewTransaction(InvoiceInterface $invoice): void;

    public function saveModel();

    public function getTransaction(): TransactionModel;

    public function setStateNew(): void;

    public function setStateSuccess(): void;

    public function setStateFail(): void;

    public function setTypeHold(): void;

    public function setTypeFinish(): void;

    public function setTypeCancel(): void;

    public function setInvoice(InvoiceInterface $invoice);
}
