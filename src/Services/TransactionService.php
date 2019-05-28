<?php

namespace viktorprogger\Accounting\Services;

use viktorprogger\Accounting\Interfaces\Services\TransactionServiceInterface;
use viktorprogger\Accounting\Interfaces\DTO\InvoiceInterface;
use viktorprogger\Accounting\Interfaces\DTO\TransactionInterface;

/**
 * Class TransactionService
 *
 * @package viktorprogger\Accounting\Services
 */
abstract class TransactionService implements TransactionServiceInterface
{
    /** @var TransactionInterface */
    protected $model;

    public function createNewTransaction(InvoiceInterface $invoice, $stateTo): void
    {
        $this->setInvoice($invoice);
        $this->model->setStateNew();
        $this->model->setInvoiceStateFrom($invoice->getState());
        $this->model->setInvoiceStateTo($stateTo);
    }

    /**
     * @return TransactionInterface
     */
    public function getModel(): TransactionInterface
    {
        return $this->model;
    }
}
