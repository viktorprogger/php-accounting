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
        if (!$invoice->isStateCreated()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "created"');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice);
        $transaction = $transactionService->getTransaction();
        $transaction->setStateNew();
        $transaction->setTypeHold();
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceService->getAccountFrom();
            $accountFromService = $this->container->getAccountService($accountFrom);
            $accountFromService->hold($invoice->getAmount());
            $accountFromService->saveModel();

            $invoice->setStateHold();
            $invoiceService->saveModel();

            $transaction->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function finish(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoice->isStateHold()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "hold"');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice);
        $transaction = $transactionService->getTransaction();
        $transaction->setStateNew();
        $transaction->setTypeFinish();
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoice->getAmount();

            $accountFrom = $invoiceService->getAccountFrom();
            $accountFromService = $this->container->getAccountService($accountFrom);
            $accountFromService->withdraw($amount);
            $accountFromService->saveModel();

            $accountTo = $invoiceService->getAccountTo();
            $accountToService = $this->container->getAccountService($accountTo);
            $accountToService->add($amount);
            $accountToService->saveModel();

            $invoice->setStateHold();
            $invoiceService->saveModel();

            $transaction->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
    }

    public function cancel(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService  = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->canCancel()) {
            throw new WrongStateException('Can\'t cancel finished invoice');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice);
        $transaction = $transactionService->getTransaction();
        $transaction->setStateNew();
        $transaction->setTypeHold();
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            if ($invoice->isStateHold()) {
                $accountFrom = $invoiceService->getAccountFrom();
                $accountFromService = $this->container->getAccountService($accountFrom);
                $accountFromService->repay($invoice->getAmount());
                $accountFromService->saveModel();
            }

            $invoice->setStateCanceled();
            $invoiceService->saveModel();

            $transaction->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
    }
}
