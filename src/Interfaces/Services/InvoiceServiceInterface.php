<?php

namespace viktorprogger\Accounting\Interfaces\Services;

use viktorprogger\Accounting\Interfaces\DTO\AccountInterface;
use viktorprogger\Accounting\Interfaces\DTO\InvoiceInterface;

/**
 * Interface InvoiceServiceInterface
 *
 * @package viktorprogger\Accounting\Interfaces\Decorators
 */
interface InvoiceServiceInterface
{
    public function __construct(InvoiceInterface $invoice);

    public function setAccountFrom(AccountInterface $account);

    public function setAccountTo(AccountInterface $account);

    public function getAccountFrom(): AccountInterface;

    public function getAccountTo(): AccountInterface;

    public function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): void;

    public function saveModel();

    public function getModel(): InvoiceInterface;

    public function canCancel(): bool;

    /**
     * Loads actual state of Invoice model from database
     *
     * @return InvoiceInterface
     */
    public function loadInvoice(): InvoiceInterface;

    public function canHold();

    public function canUnhold();
}
