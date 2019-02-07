<?php

namespace miolae\Accounting;

use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;

class Module
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
     * @return InvoiceInterface
     */
    public function createInvoice(AccountInterface $accountFrom, AccountInterface $accountTo, float $amount): InvoiceInterface
    {
        $invoiceService = $this->container->getInvoiceService();
        $invoiceService->createNewInvoice($accountFrom, $accountTo, $amount);
        $invoiceService->saveModel();

        return $invoiceService->getInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function hold(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->isStateCreated()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "created"');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice);
        $transactionService->setStateNew();
        $transactionService->setTypeHold();
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceService->getAccountFrom();
            $accountFromService = $this->container->getAccountService($accountFrom);
            $accountFromService->hold($invoiceService->getAmount());
            $accountFromService->saveModel();

            $invoiceService->setStateHold();
            $invoiceService->saveModel();

            $transactionService->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->getInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function finish(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->isStateHold()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "hold"');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice);
        $transactionService->setStateNew();
        $transactionService->setTypeFinish();
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoiceService->getAmount();

            $accountFrom = $invoiceService->getAccountFrom();
            $accountFromService = $this->container->getAccountService($accountFrom);
            $accountFromService->withdraw($amount);
            $accountFromService->saveModel();

            $accountTo = $invoiceService->getAccountTo();
            $accountToService = $this->container->getAccountService($accountTo);
            $accountToService->add($amount);
            $accountToService->saveModel();

            $invoiceService->setStateHold();
            $invoiceService->saveModel();

            $transactionService->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->getInvoice();
    }
}
