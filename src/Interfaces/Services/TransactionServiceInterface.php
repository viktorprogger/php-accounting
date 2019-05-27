<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;

/**
 * Interface TransactionServiceInterface
 *
 * @package miolae\Accounting\Interfaces\Decorators
 */
interface TransactionServiceInterface
{
    public function __construct(TransactionInterface $transaction);

    public function createNewTransaction(InvoiceInterface $invoice, $stateTo): void;

    public function saveModel();

    public function getModel(): TransactionInterface;

    public function setInvoice(InvoiceInterface $invoice);
}
