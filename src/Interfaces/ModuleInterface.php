<?php

namespace miolae\Accounting\Interfaces;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Interfaces\Services\AccountInterface as AccountService;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;
use miolae\Accounting\Interfaces\Services\TransactionInterface as TransactionService;

interface ModuleInterface
{
    public function getInvoiceService(?InvoiceInterface $invoice = null): InvoiceService;

    public function getTransactionService(?TransactionInterface $transaction = null): TransactionService;

    public function getAccountService(?AccountInterface $transaction = null): AccountService;

    public function createInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceService;

    public function hold(InvoiceInterface $invoice);
}
