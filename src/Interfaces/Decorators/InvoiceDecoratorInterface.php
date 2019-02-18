<?php

namespace miolae\Accounting\Interfaces\Decorators;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;

/**
 * Interface InvoiceDecoratorInterface
 *
 * @package miolae\Accounting\Interfaces\Decorators
 *
 * @mixin InvoiceInterface
 */
interface InvoiceDecoratorInterface
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
