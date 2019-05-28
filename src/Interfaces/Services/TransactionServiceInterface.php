<?php

namespace viktorprogger\Accounting\Interfaces\Services;

use viktorprogger\Accounting\Interfaces\DTO\InvoiceInterface;
use viktorprogger\Accounting\Interfaces\DTO\TransactionInterface;

/**
 * Interface TransactionServiceInterface
 *
 * @package viktorprogger\Accounting\Interfaces\Decorators
 */
interface TransactionServiceInterface
{
    public function __construct(TransactionInterface $transaction);

    public function createNewTransaction(InvoiceInterface $invoice, $stateTo): void;

    public function saveModel();

    public function getModel(): TransactionInterface;

    public function setInvoice(InvoiceInterface $invoice);
}
