<?php

namespace miolae\Accounting\Interfaces;

use miolae\Accounting\Interfaces\Decorators\AccountDecoratorInterface;
use miolae\Accounting\Interfaces\Decorators\InvoiceDecoratorInterface;
use miolae\Accounting\Interfaces\Decorators\TransactionDecoratorInterface;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;

interface ServiceContainerInterface
{
    public function getInvoiceDecorator(?InvoiceInterface $invoice = null): InvoiceDecoratorInterface;

    public function getTransactionDecorator(?TransactionInterface $transaction = null): TransactionDecoratorInterface;

    public function getAccountDecorator(?AccountInterface $transaction = null): AccountDecoratorInterface;

    public function getDB(): DBInterface;
}
