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
        $invoiceDecorator = $this->container->getInvoiceDecorator();
        $invoiceDecorator->createNewInvoice($accountFrom, $accountTo, $amount);
        $invoiceDecorator->saveModel();

        return $invoiceDecorator->loadInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function hold(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceDecorator = $this->container->getInvoiceDecorator($invoice);
        if (!$invoice->isStateCreated()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "created"');
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice);
        $transactionDecorator->setStateNew();
        $transactionDecorator->setTypeHold();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountDecorator = $this->container->getAccountDecorator($accountFrom);
            $accountDecorator->hold($invoice->getAmount());
            $accountDecorator->saveModel();

            $invoice->setStateHold();
            $invoiceDecorator->saveModel();

            $transactionDecorator->setStateSuccess();
            $transactionDecorator->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionDecorator->setStateFail();
            $transactionDecorator->saveModel();
        }

        return $invoiceDecorator->loadInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function finish(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceDecorator = $this->container->getInvoiceDecorator($invoice);
        if (!$invoice->isStateHold()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "hold"');
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice);
        $transactionDecorator->setStateNew();
        $transactionDecorator->setTypeFinish();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoice->getAmount();

            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountFromDecorator = $this->container->getAccountDecorator($accountFrom);
            $accountFromDecorator->withdraw($amount);
            $accountFromDecorator->saveModel();

            $accountTo = $invoiceDecorator->getAccountTo();
            $accountToDecorator = $this->container->getAccountDecorator($accountTo);
            $accountToDecorator->add($amount);
            $accountToDecorator->saveModel();

            $invoice->setStateHold();
            $invoiceDecorator->saveModel();

            $transactionDecorator->setStateSuccess();
            $transactionDecorator->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionDecorator->setStateFail();
            $transactionDecorator->saveModel();
        }

        return $invoiceDecorator->loadInvoice();
    }

    public function cancel(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceDecorator  = $this->container->getInvoiceDecorator($invoice);
        if (!$invoiceDecorator->canCancel()) {
            throw new WrongStateException('Can\'t cancel finished invoice');
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice);
        $transactionDecorator->setStateNew();
        $transactionDecorator->setTypeHold();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            if ($invoice->isStateHold()) {
                $accountFrom = $invoiceDecorator->getAccountFrom();
                $accountDecorator = $this->container->getAccountDecorator($accountFrom);
                $accountDecorator->repay($invoice->getAmount());
                $accountDecorator->saveModel();
            }

            $invoice->setStateCanceled();
            $invoiceDecorator->saveModel();

            $transactionDecorator->setStateSuccess();
            $transactionDecorator->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionDecorator->setStateFail();
            $transactionDecorator->saveModel();
        }

        return $invoiceDecorator->loadInvoice();
    }
}
