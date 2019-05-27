<?php

namespace miolae\Accounting\Services;

use miolae\Accounting\Interfaces\Decorators\InvoiceServiceInterface;
use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;

/**
 * Class InvoiceService
 *
 * @package miolae\Accounting\Services
 */
abstract class InvoiceService implements InvoiceServiceInterface
{
    /** @var InvoiceInterface */
    protected $model;

    public function __construct(InvoiceInterface $invoice)
    {
        $this->model = $invoice;
    }

    public function createNewInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): void
    {
        $this->setAccountFrom($accountFrom);
        $this->setAccountTo($accountTo);
        $this->model->setAmount($amount);
    }

    /**
     * @return InvoiceInterface
     */
    public function getModel(): InvoiceInterface
    {
        return $this->model;
    }

    /**
     * Check if associated invoice model can be canceled
     *
     * @return bool
     */
    public function canCancel(): bool
    {
        return $this->model->isStateHold() || $this->model->isStateCreated() || $this->model->isStateTransacted();
    }

    public function canHold(): bool
    {
        return $this->model->isStateCreated();
    }

    public function canUnhold(): bool
    {
        return $this->model->isStateTransacted();
    }
}
