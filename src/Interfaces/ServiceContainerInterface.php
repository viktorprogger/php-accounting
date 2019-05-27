<?php

namespace miolae\Accounting\Interfaces;

use miolae\Accounting\Interfaces\Decorators\AccountServiceInterface;
use miolae\Accounting\Interfaces\Decorators\InvoiceServiceInterface;
use miolae\Accounting\Interfaces\Decorators\TransactionServiceInterface;
use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;

interface ServiceContainerInterface
{
    public function getInvoiceService(?InvoiceInterface $invoice = null): InvoiceServiceInterface;

    public function getTransactionService(?TransactionInterface $transaction = null): TransactionServiceInterface;

    public function getAccountService(?AccountInterface $transaction = null): AccountServiceInterface;

    public function getDB(): DBInterface;
}
