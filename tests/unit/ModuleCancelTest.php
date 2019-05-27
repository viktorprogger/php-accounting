<?php

use miolae\Accounting\Services\InvoiceService;
use miolae\Accounting\Services\TransactionService;
use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\Services\AccountServiceInterface;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Module;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;

class ModuleCancelTest extends TestCase
{
    /** @var ServiceContainerInterface|ObjectProphecy */
    protected $DI;

    /** @var DBInterface|ObjectProphecy */
    protected $DB;

    /** @var TransactionInterface|ObjectProphecy */
    protected $transaction;

    /** @var TransactionService|ObjectProphecy */
    protected $transactionService;

    /** @var InvoiceInterface|ObjectProphecy */
    protected $invoice;

    /** @var InvoiceService|ObjectProphecy */
    protected $invoiceService;

    protected function setUp()
    {
        parent::setUp();
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->DB = $this->prophesize(DBInterface::class);

        $this->transaction = $this->prophesize(TransactionInterface::class);
        $this->transactionService = $this->prophesize(TransactionService::class);
        $this->transactionService->getModel()->willReturn($this->transaction->reveal());

        $this->invoice = $this->prophesize(InvoiceInterface::class);

        $this->invoiceService = $this->prophesize(InvoiceService::class);
        $this->invoiceService->getModel()->willReturn($this->invoice);

        $this->DI->getDB()->willReturn($this->DB->reveal());
        $this->DI->getTransactionService()->willReturn($this->transactionService->reveal());
        $this->DI->getInvoiceService($this->invoice->reveal())->willReturn($this->invoiceService);
    }

    /**
     * Successfully cancel an invoice which is not held or transacted
     */
    public function testOk(): void
    {
        $this->invoice->isStateHold()->willReturn(false);
        $this->invoice->isStateTransacted()->willReturn(false);
        $this->invoice->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoice->getStateCanceled()->shouldBeCalledTimes(1);
        $this->invoiceService->canCancel()->willReturn(true);
        $this->invoiceService->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceService->saveModel()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        $this->transaction->setStateSuccess()->shouldBeCalledTimes(1);
        $this->transactionService->createNewTransaction($this->invoice->reveal(), null);
        $this->transactionService->saveModel()->shouldBeCalledTimes(2);

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    /**
     * Successfully cancel an invoice which is held
     */
    public function testOkHold(): void
    {
        $amount = random_int(100, 1000) / 100;

        $prophecyAccountFromService = $this->prophesize(AccountServiceInterface::class);
        $prophecyAccountFromService->repay($amount)->shouldBeCalledTimes(1);
        $prophecyAccountFromService->saveModel()->shouldBeCalledTimes(1);

        $this->invoice->isStateHold()->willReturn(true);
        $this->invoice->isStateTransacted()->willReturn(false);
        $this->invoice->getAmount()->willReturn($amount);
        $this->invoice->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoice->getStateCanceled()->shouldBeCalledTimes(1);

        $this->invoiceService->canCancel()->willReturn(true);
        $this->invoiceService->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceService->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceService->getAccountFrom()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        $this->transaction->setStateSuccess()->shouldBeCalledTimes(1);
        $this->transactionService->createNewTransaction($this->invoice->reveal(), null);
        $this->transactionService->saveModel()->shouldBeCalledTimes(2);
        $this->DI->getTransactionService()->willReturn($this->transactionService->reveal());

        $this->DI
            ->getAccountService(new TypeToken(AccountInterface::class))
            ->willReturn($prophecyAccountFromService->reveal());

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    /**
     * Successfully cancel an invoice which is transacted
     */
    public function testOkTransacted(): void
    {
        $amount = random_int(100, 1000) / 100;

        $prophecyAccountFromService = $this->prophesize(AccountServiceInterface::class);
        $prophecyAccountFromService->add($amount)->shouldBeCalledTimes(1);
        $prophecyAccountFromService->saveModel()->shouldBeCalledTimes(1);

        $prophecyAccountToService = $this->prophesize(AccountServiceInterface::class);
        $prophecyAccountToService->repay($amount)->shouldBeCalledTimes(1);
        $prophecyAccountToService->withdraw($amount)->shouldBeCalledTimes(1);
        $prophecyAccountToService->saveModel()->shouldBeCalledTimes(1);

        $this->invoice->isStateHold()->willReturn(false);
        $this->invoice->isStateTransacted()->willReturn(true);
        $this->invoice->getAmount()->willReturn($amount);
        $this->invoice->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoice->getStateCanceled()->shouldBeCalledTimes(1);

        $this->invoiceService->canCancel()->willReturn(true);
        $this->invoiceService->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceService->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceService->getAccountFrom()->shouldBeCalledTimes(1);
        $this->invoiceService->getAccountTo()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        $this->transaction->setStateSuccess()->shouldBeCalledTimes(1);
        $this->transactionService->createNewTransaction($this->invoice->reveal(), null);
        $this->transactionService->saveModel()->shouldBeCalledTimes(2);
        $this->DI->getTransactionService()->willReturn($this->transactionService->reveal());

        $this->DI
            ->getAccountService(new TypeToken(AccountInterface::class))
            ->willReturn($prophecyAccountFromService->reveal(), $prophecyAccountToService->reveal());

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    public function testWrongState(): void
    {
        $this->expectException(WrongStateException::class);

        $this->invoiceService->canCancel()->willReturn(false);
        $this->transactionService->saveModel()->shouldNotBeCalled();

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    // TODO Need to test exceptions on all stages in all module methods
    // TODO Maybe it's a way to make another class for exception checking
    public function testException(): void
    {
        /** @var ExceptionInterface|ObjectProphecy $exception */
        $exception = $this->prophesize(ExceptionInterface::class);

        $this->invoice->isStateHold()->willReturn(false);
        $this->invoice->isStateTransacted()->willReturn(false);
        $this->invoice->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoice->getStateCanceled()->shouldBeCalledTimes(1);

        $this->invoiceService->canCancel()->willReturn(true);
        $this->invoiceService->saveModel()->willThrow($exception->reveal());
        $this->invoiceService->loadInvoice()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldNotBeCalled();
        $this->DB->rollback()->shouldBeCalledTimes(1);

        $this->transaction->setStateFail()->shouldBeCalledTimes(1);
        $this->transaction->setStateSuccess()->shouldNotBeCalled();

        $this->transactionService->createNewTransaction($this->invoice->reveal(), null)->shouldBeCalledTimes(1);
        $this->transactionService->saveModel()->shouldBeCalledTimes(2);

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }
}
