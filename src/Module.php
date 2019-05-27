<?php

namespace miolae\Accounting;

use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
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
        $invoiceDecorator = $this->container->getInvoiceService();
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
        $invoiceDecorator = $this->container->getInvoiceService($invoice);
        if (!$invoiceDecorator->canHold()) {
            throw new WrongStateException('Invoice can\'t be held because its state is not "created"');
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionService();
        $transactionDecorator->createNewTransaction($invoice, $invoice->getStateHold());
        $transactionDecorator->setStateNew();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountDecorator = $this->container->getAccountService($accountFrom);
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
     * Move funds from one account to another
     *
     * @param InvoiceInterface $invoice Invoice in which funds are transferred
     *
     * @param bool             $hold    If we need to hold transacted funds
     *
     * @return InvoiceInterface
     */
    public function transact(InvoiceInterface $invoice, bool $hold = false): InvoiceInterface
    {
        $invoiceDecorator = $this->container->getInvoiceService($invoice);
        if (!$invoiceDecorator->canHold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $stateTo = $hold ? $invoice->getStateTransacted() : $invoice->getStateSuccess();

        $transactionDecorator = $this->container->getTransactionService();
        $transactionDecorator->createNewTransaction($invoice, $stateTo);
        $transactionDecorator->setStateNew();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoice->getAmount();

            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountFromDecorator = $this->container->getAccountService($accountFrom);
            $accountFromDecorator->withdraw($amount);
            $accountFromDecorator->saveModel();

            $accountTo = $invoiceDecorator->getAccountTo();
            $accountToDecorator = $this->container->getAccountService($accountTo);
            $accountToDecorator->add($amount);
            if ($hold) {
                $accountToDecorator->hold($amount);
            }
            $accountToDecorator->saveModel();

            if ($hold) {
                $invoice->setStateTransacted();
            } else {
                $invoice->setStateSuccess();
            }
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
     * Finalizes invoices in "transacted" state and disables hold for the funds
     * TODO This method must be renamed...
     *
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function unhold(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceDecorator = $this->container->getInvoiceService($invoice);
        if (!$invoiceDecorator->canUnhold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionService();
        $transactionDecorator->createNewTransaction($invoice, $invoice->getStateSuccess());
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $account = $invoiceDecorator->getAccountTo();
            $accountDecorator = $this->container->getAccountService($account);
            $accountDecorator->repay($invoice->getAmount());
            $accountDecorator->saveModel();

            $invoice->setStateSuccess();
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
        $invoiceDecorator  = $this->container->getInvoiceService($invoice);
        if (!$invoiceDecorator->canCancel()) {
            throw new WrongStateException('Can\'t cancel finished invoice');
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionService();
        $transactionDecorator->createNewTransaction($invoice, $invoice->getStateCanceled());
        $transactionDecorator->setStateNew();
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            if ($invoiceDecorator->isStateHold()) {
                $accountFrom = $invoiceDecorator->getAccountFrom();
                $accountFromDecorator = $this->container->getAccountService($accountFrom);
                $accountFromDecorator->repay($invoiceDecorator->getAmount());
                $accountFromDecorator->saveModel();
            }

            if ($invoiceDecorator->isStateTransacted()) {
                $accountFrom = $invoiceDecorator->getAccountFrom();
                $accountFromDecorator = $this->container->getAccountService($accountFrom);
                $accountFromDecorator->add($invoice->getAmount());
                $accountFromDecorator->saveModel();

                $accountTo = $invoiceDecorator->getAccountTo();
                $accountToDecorator = $this->container->getAccountService($accountTo);
                $accountToDecorator->repay($invoice->getAmount());
                $accountToDecorator->withdraw($invoice->getAmount());
                $accountToDecorator->saveModel();
            }

            $invoiceDecorator->setStateCanceled();
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
