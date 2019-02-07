<?php

namespace miolae\Accounting;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ModuleInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;

class Module implements ModuleInterface
{
    /** @var ServiceContainerInterface */
    protected $container;

    public function __construct(ServiceContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param AccountInterface $accountFrom
     * @param AccountInterface $accountTo
     * @param float            $amount
     *
     * @return InvoiceService
     */
    public function createInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceService
    {
        $invoiceService = $this->container->getInvoiceService();
        $invoiceService->createNewInvoice($accountFrom, $accountTo, $amount);
        $invoiceService->saveModel();

        return $invoiceService;
    }

    public function hold(InvoiceInterface $invoice)
    {
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->isStateCreated()) {
            // TODO add an error
        }


    }
}
