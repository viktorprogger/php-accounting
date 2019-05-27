<?php

namespace miolae\Accounting;

use miolae\Accounting\Exceptions\SameAccountException;
use miolae\Accounting\Exceptions\WrongAmountException;
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
        if ($amount <= 0) {
            throw new WrongAmountException($amount);
        }

        if ($accountFrom === $accountTo) {
            throw new SameAccountException();
        }

        $invoiceService = $this->container->getInvoiceService();
        $invoiceService->createNewInvoice($accountFrom, $accountTo, $amount);
        $invoiceService->saveModel();

        return $invoiceService->loadInvoice();
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return InvoiceInterface
     */
    public function hold(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->canHold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice, $invoiceService->getModel()->getStateHold());
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceService->getAccountFrom();
            $accountService = $this->container->getAccountService($accountFrom);
            $accountService->hold($invoiceService->getModel()->getAmount());
            $accountService->saveModel();

            $invoiceService->getModel()->setStateHold();
            $invoiceService->saveModel();

            $transactionService->getModel()->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->getModel()->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
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
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->canHold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $stateTo = $hold ? $invoice->getStateTransacted() : $invoice->getStateSuccess();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice, $stateTo);
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $amount = $invoiceService->getModel()->getAmount();

            $accountFrom = $invoiceService->getAccountFrom();
            $accountFromService = $this->container->getAccountService($accountFrom);
            $accountFromService->withdraw($amount);
            $accountFromService->saveModel();

            $accountTo = $invoiceService->getAccountTo();
            $accountToService = $this->container->getAccountService($accountTo);
            $accountToService->add($amount);
            if ($hold) {
                $accountToService->hold($amount);
            }
            $accountToService->saveModel();

            if ($hold) {
                $invoiceService->getModel()->setStateTransacted();
            } else {
                $invoiceService->getModel()->setStateSuccess();
            }
            $invoiceService->saveModel();

            $transactionService->getModel()->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->getModel()->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
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
        $invoiceService = $this->container->getInvoiceService($invoice);
        if (!$invoiceService->canUnhold()) {
            throw new WrongStateException();
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transactionService->createNewTransaction($invoice, $invoice->getStateSuccess());
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            $account = $invoiceService->getAccountTo();
            $accountService = $this->container->getAccountService($account);
            $accountService->repay($invoiceService->getModel()->getAmount());
            $accountService->saveModel();

            $invoiceService->getModel()->setStateSuccess();
            $invoiceService->saveModel();

            $transactionService->getModel()->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->getModel()->setStateFail();
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
        $transactionService->createNewTransaction($invoice, $invoice->getStateCanceled());
        $transactionService->saveModel();

        $db->beginTransaction();

        try {
            if ($invoiceService->getModel()->isStateHold()) {
                $accountFrom = $invoiceService->getAccountFrom();
                $accountFromService = $this->container->getAccountService($accountFrom);
                $accountFromService->repay($invoiceService->getModel()->getAmount());
                $accountFromService->saveModel();
            }

            if ($invoiceService->getModel()->isStateTransacted()) {
                $accountFrom = $invoiceService->getAccountFrom();
                $accountFromService = $this->container->getAccountService($accountFrom);
                $accountFromService->add($invoiceService->getModel()->getAmount());
                $accountFromService->saveModel();

                $accountTo = $invoiceService->getAccountTo();
                $accountToService = $this->container->getAccountService($accountTo);
                $accountToService->repay($invoiceService->getModel()->getAmount());
                $accountToService->withdraw($invoiceService->getModel()->getAmount());
                $accountToService->saveModel();
            }

            $invoiceService->getModel()->setStateCanceled();
            $invoiceService->saveModel();

            $transactionService->getModel()->setStateSuccess();
            $transactionService->saveModel();

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transactionService->getModel()->setStateFail();
            $transactionService->saveModel();
        }

        return $invoiceService->loadInvoice();
    }
}
