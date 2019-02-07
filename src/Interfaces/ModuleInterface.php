<?php

namespace miolae\Accounting\Interfaces;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;

interface ModuleInterface
{
    public function createInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceService;

    public function hold(InvoiceInterface $invoice);
}
