<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface as InvoiceModel;

interface InvoiceInterface
{
    public static function setAccountFrom(InvoiceModel $invoice, AccountInterface $account);

    public static function getAccountFrom(InvoiceModel $invoice): AccountInterface;

    public static function setAccountTo(InvoiceModel $invoice, AccountInterface $account);

    public static function getAccountTo(InvoiceModel $invoice): AccountInterface;

    public static function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceModel;

    public static function saveModel(InvoiceModel $invoice): InvoiceModel;

    public static function canCancel(InvoiceModel $invoice): bool;

    /**
     * Loads actual state of Invoice model from database
     *
     * @param InvoiceModel $invoice
     *
     * @return InvoiceModel
     */
    public static function loadInvoice(InvoiceModel $invoice): InvoiceModel;
}
