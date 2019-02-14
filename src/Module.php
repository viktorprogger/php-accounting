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
        $invoice = $invoiceService::createNewInvoice($accountFrom, $accountTo, $amount);
        $invoice = $invoiceService::saveModel($invoice);

        return $invoice;
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
        $transaction = $transactionService::createNewTransaction($invoice);
        $transaction->setStateNew();
        $transaction->setTypeHold();
        $transactionService::saveModel($transaction);

        $db->beginTransaction();

        try {
            $accountFrom = $invoiceService::getAccountFrom($invoice);
            $accountService = $this->container->getAccountService($accountFrom);
            $accountService::hold($accountFrom, $invoice->getAmount());
            $accountService::saveModel($accountFrom);

            $invoice->setStateHold();
            $invoiceService::saveModel($invoice);

            $transaction->setStateSuccess();
            $transactionService::saveModel($transaction);

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService::saveModel($transaction);
        }

        return $invoiceService::loadInvoice($invoice);
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
        $transaction = $transactionService::createNewTransaction($invoice);
        $transaction->setStateNew();
        $transaction->setTypeFinish();
        $transactionService::saveModel($transaction);

        $db->beginTransaction();

        try {
            $amount = $invoice->getAmount();

            $accountFrom = $invoiceService::getAccountFrom($invoice);
            $accountService = $this->container->getAccountService($accountFrom);
            $accountService::withdraw($accountFrom, $amount);
            $accountService::saveModel($accountFrom);

            $accountTo = $invoiceService::getAccountTo($invoice);
            $accountService::add($accountTo, $amount);
            $accountService::saveModel($accountTo);

            $invoice->setStateHold();
            $invoiceService::saveModel($invoice);

            $transaction->setStateSuccess();
            $transactionService::saveModel($transaction);

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService::saveModel($transaction);
        }

        return $invoiceService::loadInvoice($invoice);
    }

    public function cancel(InvoiceInterface $invoice): InvoiceInterface
    {
        $invoiceService  = $this->container->getInvoiceService($invoice);
        if (!$invoiceService::canCancel($invoice)) {
            throw new WrongStateException('Can\'t cancel finished invoice');
        }

        $db = $this->container->getDB();

        $transactionService = $this->container->getTransactionService();
        $transaction = $transactionService::createNewTransaction($invoice);
        $transaction->setStateNew();
        $transaction->setTypeHold();
        $transactionService::saveModel($transaction);

        $db->beginTransaction();

        try {
            if ($invoice->isStateHold()) {
                $accountFrom = $invoiceService::getAccountFrom($invoice);
                $accountService = $this->container->getAccountService($accountFrom);
                $accountService::repay($accountFrom, $invoice->getAmount());
                $accountService::saveModel($accountFrom);
            }

            $invoice->setStateCanceled();
            $invoiceService::saveModel($invoice);

            $transaction->setStateSuccess();
            $transactionService::saveModel($transaction);

            $db->commit();
        } catch (ExceptionInterface $e) {
            $db->rollback();
            $transaction->setStateFail();
            $transactionService::saveModel($transaction);
        }

        return $invoiceService::loadInvoice($invoice);
    }
}
