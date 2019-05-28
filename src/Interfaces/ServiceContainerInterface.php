<?php

namespace viktorprogger\Accounting\Interfaces;

use viktorprogger\Accounting\Interfaces\DTO\AccountInterface;
use viktorprogger\Accounting\Interfaces\DTO\InvoiceInterface;
use viktorprogger\Accounting\Interfaces\DTO\TransactionInterface;
use viktorprogger\Accounting\Interfaces\Services\AccountServiceInterface;
use viktorprogger\Accounting\Interfaces\Services\DBInterface;
use viktorprogger\Accounting\Interfaces\Services\InvoiceServiceInterface;
use viktorprogger\Accounting\Interfaces\Services\TransactionServiceInterface;

interface ServiceContainerInterface
{
    public function getInvoiceService(?InvoiceInterface $invoice = null): InvoiceServiceInterface;

    public function getTransactionService(?TransactionInterface $transaction = null): TransactionServiceInterface;

    public function getAccountService(?AccountInterface $transaction = null): AccountServiceInterface;

    public function getDB(): DBInterface;
}
