<?php

namespace miolae\Accounting;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ModuleInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;

abstract class Module implements ModuleInterface
{
    /**
     * @param AccountInterface $accountFrom
     * @param AccountInterface $accountTo
     * @param float            $amount
     *
     * @return InvoiceService
     */
    public function createInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceService
    {
        $invoiceService = $this->getInvoiceService();
        $invoiceService->createNewInvoice($accountFrom, $accountTo, $amount);
        $invoiceService->saveModel();

        return $invoiceService;
    }

    public function hold(InvoiceInterface $invoice)
    {
        $invoiceService = $this->getInvoiceService($invoice);
        if (!$invoiceService->isStateCreated()) {
            // TODO add an error
        }


    }
}
