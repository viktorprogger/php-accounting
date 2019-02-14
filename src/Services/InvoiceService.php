<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceServiceInterface;

abstract class InvoiceService implements InvoiceServiceInterface
{
    public static function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceInterface
    {
        $invoice = static::getInvoiceInstance();

        static::setAccountFrom($invoice, $accountFrom);
        static::setAccountTo($invoice, $accountTo);
        $invoice->setAmount($amount);

        return $invoice;
    }

    public static function canCancel(InvoiceInterface $invoice): bool
    {
        return !$invoice->isStateHold() && !$invoice->isStateCreated();
    }

    abstract protected static function getInvoiceInstance(): InvoiceInterface;
}
