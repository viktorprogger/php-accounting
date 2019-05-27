<?php

namespace miolae\Accounting\Interfaces;

use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;
use miolae\Accounting\Interfaces\Services\AccountServiceInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Interfaces\Services\InvoiceServiceInterface;
use miolae\Accounting\Interfaces\Services\TransactionServiceInterface;

interface ServiceContainerInterface
{
    public function getInvoiceService(?InvoiceInterface $invoice = null): InvoiceServiceInterface;

    public function getTransactionService(?TransactionInterface $transaction = null): TransactionServiceInterface;

    public function getAccountService(?AccountInterface $transaction = null): AccountServiceInterface;

    public function getDB(): DBInterface;
}
