<?php

namespace miolae\Accounting\Interfaces\Decorators;

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;

/**
 * Interface TransactionDecoratorInterface
 *
 * @package miolae\Accounting\Interfaces\Decorators
 *
 * @mixin TransactionInterface
 */
interface TransactionDecoratorInterface
{
    public function __construct(TransactionInterface $transaction);

    public function createNewTransaction(InvoiceInterface $invoice, $stateTo): void;

    public function saveModel();

    public function getModel(): TransactionInterface;

    public function setInvoice(InvoiceInterface $invoice);
}
