<?php

use miolae\Accounting\Decorators\AccountDecorator;
use miolae\Accounting\Decorators\InvoiceDecorator;
use miolae\Accounting\Decorators\TransactionDecorator;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\Models\TransactionInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Module;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;

class ModuleHoldTest extends TestCase
{
    const STATE_HOLD = 'hold';
    const AMOUNT = 100;

    /** @var ServiceContainerInterface|ObjectProphecy $DI */
    protected $DI;

    /** @var DBInterface|ObjectProphecy */
    protected $DB;

    /** @var InvoiceDecorator|InvoiceInterface|ObjectProphecy $DI */
    protected $invoiceDecorator;

    /** @var TransactionDecorator|TransactionInterface|ObjectProphecy */
    protected $transaction;

    /** @var InvoiceInterface|ObjectProphecy */
    protected $invoice;

    /** @var AccountDecorator|AccountInterface */
    protected $accountFrom;

    protected function setUp()
    {
        parent::setUp();

        /** @var ServiceContainerInterface|ObjectProphecy $DI */
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->DB = $this->prophesize(DBInterface::class);

        $this->transaction = $this->prophesize(TransactionDecorator::class);
        $this->transaction->willImplement(TransactionInterface::class);

        $this->invoice = $this->prophesize(InvoiceInterface::class);
        $this->accountFrom = $this->prophesize(AccountDecorator::class);

        $this->invoiceDecorator = $this->prophesize(InvoiceDecorator::class);
        $this->invoiceDecorator->willImplement(InvoiceInterface::class);
        $this->invoiceDecorator->getStateHold()->willReturn(self::STATE_HOLD);
        $this->invoiceDecorator->loadInvoice()->willReturn($this->prophesize(InvoiceInterface::class)->reveal());
        $this->invoiceDecorator->getAmount()->willReturn(self::AMOUNT);

        $this->DI->getDB()->willReturn($this->DB->reveal());
        $this->DI->getTransactionDecorator()->willReturn($this->transaction->reveal());
        $this->DI->getInvoiceDecorator($this->invoice->reveal())->willReturn($this->invoiceDecorator->reveal());
        $this->DI->getAccountDecorator(new TypeToken(AccountInterface::class))->willReturn($this->accountFrom->reveal());
    }

    public function testOk()
    {
        $this->invoiceDecorator->canHold()->willReturn(true);
        $this->invoiceDecorator->getAccountFrom()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->setStateHold()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceDecorator->loadInvoice()->shouldBeCalledTimes(1);

        $this->transaction->createNewTransaction($this->invoice->reveal(), self::STATE_HOLD)->shouldBeCalledTimes(1);
        $this->transaction->saveModel()->shouldBeCalledTimes(2);
        $this->transaction->setStateSuccess()->shouldBeCalledTimes(1);
        $this->transaction->setStateFail()->shouldNotBeCalled();

        $this->DB->beginTransaction()->shouldBeCalledTimes(1);
        $this->DB->commit()->shouldBeCalledTimes(1);
        $this->DB->rollback()->shouldNotBeCalled();

        $this->accountFrom->hold(self::AMOUNT)->shouldBeCalledTimes(1);
        $this->accountFrom->saveModel()->shouldBeCalledTimes(1);

        $module = new Module($this->DI->reveal());
        $module->hold($this->invoice->reveal());
    }
}
