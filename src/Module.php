<?php

namespace miolae\Accounting;

use miolae\Accounting\Exceptions\SameAccountException;
use miolae\Accounting\Exceptions\WrongAmountException;
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
        if ($amount <= 0) {
            throw new WrongAmountException($amount);
        }

        if ($accountFrom === $accountTo) {
            throw new SameAccountException();
        }

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
        if (!$invoiceDecorator->canHold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice, $invoiceDecorator->getStateHold());
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountDecorator = $this->container->getAccountDecorator($accountFrom);
            $accountDecorator->hold($invoiceDecorator->getAmount());
            $accountDecorator->saveModel();

            $invoiceDecorator->setStateHold();
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
        $invoiceDecorator = $this->container->getInvoiceDecorator($invoice);
        if (!$invoiceDecorator->canHold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $stateTo = $hold ? $invoice->getStateTransacted() : $invoice->getStateSuccess();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice, $stateTo);
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoiceDecorator->getAmount();

            $accountFrom = $invoiceDecorator->getAccountFrom();
            $accountFromDecorator = $this->container->getAccountDecorator($accountFrom);
            $accountFromDecorator->withdraw($amount);
            $accountFromDecorator->saveModel();

            $accountTo = $invoiceDecorator->getAccountTo();
            $accountToDecorator = $this->container->getAccountDecorator($accountTo);
            $accountToDecorator->add($amount);
            if ($hold) {
                $accountToDecorator->hold($amount);
            }
            $accountToDecorator->saveModel();

            if ($hold) {
                $invoiceDecorator->setStateTransacted();
            } else {
                $invoiceDecorator->setStateSuccess();
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
        $invoiceDecorator = $this->container->getInvoiceDecorator($invoice);
        if (!$invoiceDecorator->canUnhold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $transactionDecorator = $this->container->getTransactionDecorator();
        $transactionDecorator->createNewTransaction($invoice, $invoice->getStateSuccess());
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            $account = $invoiceDecorator->getAccountTo();
            $accountDecorator = $this->container->getAccountDecorator($account);
            $accountDecorator->repay($invoiceDecorator->getAmount());
            $accountDecorator->saveModel();

            $invoiceDecorator->setStateSuccess();
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
        $transactionDecorator->createNewTransaction($invoice, $invoice->getStateCanceled());
        $transactionDecorator->saveModel();

        $db->beginTransaction();

        try {
            if ($invoiceDecorator->isStateHold()) {
                $accountFrom = $invoiceDecorator->getAccountFrom();
                $accountFromDecorator = $this->container->getAccountDecorator($accountFrom);
                $accountFromDecorator->repay($invoiceDecorator->getAmount());
                $accountFromDecorator->saveModel();
            }

            if ($invoiceDecorator->isStateTransacted()) {
                $accountFrom = $invoiceDecorator->getAccountFrom();
                $accountFromDecorator = $this->container->getAccountDecorator($accountFrom);
                $accountFromDecorator->add($invoiceDecorator->getAmount());
                $accountFromDecorator->saveModel();

                $accountTo = $invoiceDecorator->getAccountTo();
                $accountToDecorator = $this->container->getAccountDecorator($accountTo);
                $accountToDecorator->repay($invoiceDecorator->getAmount());
                $accountToDecorator->withdraw($invoiceDecorator->getAmount());
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
