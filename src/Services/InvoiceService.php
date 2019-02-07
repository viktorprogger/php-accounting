<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceServiceInterface;

abstract class InvoiceService implements InvoiceServiceInterface
{
    /** @var InvoiceInterface */
    protected $invoice;

    public function __construct(InvoiceInterface $invoice)
    {
        $this->invoice = $invoice;
    }

    public function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): void
    {
        $this->setAccountFrom($accountFrom);
        $this->setAccountTo($accountTo);
        $this->setAmount($amount);
    }

    /**
     * @return InvoiceInterface
     */
    public function getInvoice(): InvoiceInterface
    {
        return $this->invoice;
    }
}
