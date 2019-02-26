<?php

use miolae\Accounting\Decorators\InvoiceDecorator;
use miolae\Accounting\Decorators\TransactionDecorator;
use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\Decorators\AccountDecoratorInterface;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
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

    /** @var TransactionDecorator|TransactionInterface|ObjectProphecy */
    protected $transaction;

    /** @var InvoiceInterface|ObjectProphecy */
    protected $invoice;

    /** @var InvoiceDecorator|InvoiceInterface|ObjectProphecy */
    protected $invoiceDecorator;

    protected function setUp()
    {
        parent::setUp();
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->DB = $this->prophesize(DBInterface::class);

        $this->transaction = $this->prophesize(TransactionDecorator::class);
        $this->transaction->willImplement(TransactionInterface::class);

        $this->invoice = $this->prophesize(InvoiceInterface::class);

        $this->invoiceDecorator = $this->prophesize(InvoiceDecorator::class);
        $this->invoiceDecorator->willImplement(InvoiceInterface::class);

        $this->DI->getDB()->willReturn($this->DB->reveal());
        $this->DI->getTransactionDecorator()->willReturn($this->transaction->reveal());
        $this->DI->getInvoiceDecorator($this->invoice->reveal())->willReturn($this->invoiceDecorator);
    }

    /**
     * Successfully cancel an invoice which is not held or transacted
     */
    public function testOk(): void
    {
        $this->invoiceDecorator->isStateHold()->willReturn(false);
        $this->invoiceDecorator->isStateTransacted()->willReturn(false);
        $this->invoiceDecorator->canCancel()->willReturn(true);
        $this->invoiceDecorator->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->saveModel()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        /** @var TransactionDecorator|TransactionInterface|ObjectProphecy $prophecyTransaction */
        $prophecyTransaction = $this->prophesize(TransactionDecorator::class);
        $prophecyTransaction->willImplement(TransactionInterface::class);
        $prophecyTransaction->createNewTransaction($this->invoice->reveal(), null);
        $prophecyTransaction->setStateSuccess()->shouldBeCalledTimes(1);
        $prophecyTransaction->saveModel()->shouldBeCalledTimes(2);
        $this->DI->getTransactionDecorator()->willReturn($prophecyTransaction->reveal());

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    /**
     * Successfully cancel an invoice which is held
     */
    public function testOkHold(): void
    {
        $amount = random_int(100, 1000) / 100;

        $prophecyAccountFromDecorator = $this->prophesize(AccountDecoratorInterface::class);
        $prophecyAccountFromDecorator->repay($amount)->shouldBeCalledTimes(1);
        $prophecyAccountFromDecorator->saveModel()->shouldBeCalledTimes(1);

        $this->invoiceDecorator->isStateHold()->willReturn(true);
        $this->invoiceDecorator->isStateTransacted()->willReturn(false);
        $this->invoiceDecorator->canCancel()->willReturn(true);
        $this->invoiceDecorator->getAmount()->willReturn($amount);
        $this->invoiceDecorator->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->getAccountFrom()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        /** @var TransactionDecorator|TransactionInterface|ObjectProphecy $prophecyTransaction */
        $prophecyTransaction = $this->prophesize(TransactionDecorator::class);
        $prophecyTransaction->willImplement(TransactionInterface::class);
        $prophecyTransaction->createNewTransaction($this->invoice->reveal(), null);
        $prophecyTransaction->setStateSuccess()->shouldBeCalledTimes(1);
        $prophecyTransaction->saveModel()->shouldBeCalledTimes(2);
        $this->DI->getTransactionDecorator()->willReturn($prophecyTransaction->reveal());

        $this->DI
            ->getAccountDecorator(new TypeToken(AccountInterface::class))
            ->willReturn($prophecyAccountFromDecorator->reveal());

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    /**
     * Successfully cancel an invoice which is transacted
     */
    public function testOkTransacted(): void
    {
        $amount = random_int(100, 1000) / 100;

        $prophecyAccountFromDecorator = $this->prophesize(AccountDecoratorInterface::class);
        $prophecyAccountFromDecorator->add($amount)->shouldBeCalledTimes(1);
        $prophecyAccountFromDecorator->saveModel()->shouldBeCalledTimes(1);

        $prophecyAccountToDecorator = $this->prophesize(AccountDecoratorInterface::class);
        $prophecyAccountToDecorator->repay($amount)->shouldBeCalledTimes(1);
        $prophecyAccountToDecorator->withdraw($amount)->shouldBeCalledTimes(1);
        $prophecyAccountToDecorator->saveModel()->shouldBeCalledTimes(1);

        $this->invoiceDecorator->isStateHold()->willReturn(false);
        $this->invoiceDecorator->isStateTransacted()->willReturn(true);
        $this->invoiceDecorator->canCancel()->willReturn(true);
        $this->invoiceDecorator->getAmount()->willReturn($amount);
        $this->invoiceDecorator->loadInvoice()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->getAccountFrom()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->getAccountTo()->shouldBeCalledTimes(1);

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        /** @var TransactionDecorator|TransactionInterface|ObjectProphecy $prophecyTransaction */
        $prophecyTransaction = $this->prophesize(TransactionDecorator::class);
        $prophecyTransaction->willImplement(TransactionInterface::class);
        $prophecyTransaction->createNewTransaction($this->invoice->reveal(), null);
        $prophecyTransaction->setStateSuccess()->shouldBeCalledTimes(1);
        $prophecyTransaction->saveModel()->shouldBeCalledTimes(2);
        $this->DI->getTransactionDecorator()->willReturn($prophecyTransaction->reveal());

        $this->DI
            ->getAccountDecorator(new TypeToken(AccountInterface::class))
            ->willReturn($prophecyAccountFromDecorator->reveal(), $prophecyAccountToDecorator->reveal());

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    public function testWrongState(): void
    {
        $this->expectException(WrongStateException::class);

        $this->invoiceDecorator->canCancel()->willReturn(false);
        $this->transaction->saveModel()->shouldNotBeCalled();

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }

    // TODO Need to test exceptions on all stages in all module methods
    // TODO Maybe it's a way to make another class for exception checking
    public function testException(): void
    {
        /** @var ExceptionInterface|ObjectProphecy $exception */
        $exception = $this->prophesize(ExceptionInterface::class);

        $this->invoiceDecorator->isStateHold()->willReturn(false);
        $this->invoiceDecorator->isStateTransacted()->willReturn(false);
        $this->invoiceDecorator->canCancel()->willReturn(true);
        $this->invoiceDecorator->saveModel()->willThrow($exception->reveal());

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldNotBeCalled();
        $this->DB->rollback()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->setStateCanceled()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->loadInvoice()->shouldBeCalledTimes(1);

        $this->transaction->createNewTransaction($this->invoice->reveal(), null)->shouldBeCalledTimes(1);
        $this->transaction->saveModel()->shouldBeCalledTimes(2);
        $this->transaction->setStateFail()->shouldBeCalledTimes(1);
        $this->transaction->setStateSuccess()->shouldNotBeCalled();

        $module = new Module($this->DI->reveal());
        $module->cancel($this->invoice->reveal());
    }
}
