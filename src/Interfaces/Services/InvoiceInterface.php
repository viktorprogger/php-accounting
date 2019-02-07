<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface as InvoiceModel;

interface InvoiceInterface
{
    public function __construct(InvoiceModel $invoice);

    public function setAccountFrom(AccountInterface $account);

    public function setAccountTo(AccountInterface $account);

    public function getAccountFrom(): AccountInterface;

    public function getAccountTo(): AccountInterface;

    public function setAmount(float $amount);

    public function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): void;

    public function saveModel();

    public function getInvoice(): InvoiceModel;

    public function setStateCreated();

    public function setStateHold();

    public function setStateSuccess();

    public function setStateCanceled();

    public function isStateCreated(): bool;

    public function isStateHold(): bool;

    public function isStateSuccess(): bool;

    public function isStateCanceled(): bool;

    public function getAmount(): float;

    public function canCancel(): bool;

    /**
     * Loads actual state of Invoice model from database
     *
     * @return InvoiceModel
     */
    public function loadInvoice(): InvoiceModel;
}
