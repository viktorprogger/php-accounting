<?php

use miolae\Accounting\Decorators\InvoiceDecorator;
use miolae\Accounting\Exceptions\SameAccountException;
use miolae\Accounting\Exceptions\WrongAmountException;
use miolae\Accounting\Interfaces\Models\AccountInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Module;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ModuleCreateInvoiceTest extends TestCase
{
    /** @var ServiceContainerInterface|ObjectProphecy $DI */
    protected $DI;
    /** @var InvoiceDecorator|ObjectProphecy $DI */
    protected $invoiceDecorator;
    /** @var AccountInterface */
    protected $accountFrom;
    /** @var AccountInterface */
    protected $accountTo;

    protected function setUp()
    {
        parent::setUp();

        /** @var ServiceContainerInterface|ObjectProphecy $DI */
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->invoiceDecorator = $this->prophesize(InvoiceDecorator::class);
        $this->invoiceDecorator->loadInvoice()->willReturn($this->prophesize(InvoiceInterface::class)->reveal());
        $this->DI->getInvoiceDecorator()->willReturn($this->invoiceDecorator->reveal());

        $this->accountFrom = $this->prophesize(AccountInterface::class)->reveal();
        $this->accountTo = $this->prophesize(AccountInterface::class)->reveal();
    }

    public function testOk(): void
    {
        $amount = 0.01;
        $this->invoiceDecorator->createNewInvoice($this->accountFrom, $this->accountTo, $amount)->shouldBeCalledTimes(1);
        $this->invoiceDecorator->saveModel()->shouldBeCalledTimes(1);

        (new Module($this->DI->reveal()))->createInvoice($this->accountFrom, $this->accountTo, $amount);
    }

    /**
     * @dataProvider wrongAmountProvider
     */
    public function testWrongAmount($amount)
    {
        $message = "Funds amount must be a positive number, \"$amount\" given";
        $this->expectException(WrongAmountException::class);
        $this->expectExceptionMessage($message);
        $this->invoiceDecorator->createNewInvoice($this->accountFrom, $this->accountTo, $amount)->shouldNotBeCalled();

        (new Module($this->DI->reveal()))->createInvoice($this->accountFrom, $this->accountTo, $amount);
    }

    public function testWrongAccount()
    {
        $amount = 1;
        $this->expectException(SameAccountException::class);
        $this->invoiceDecorator->createNewInvoice($this->accountFrom, $this->accountFrom, $amount)->shouldNotBeCalled();

        (new Module($this->DI->reveal()))->createInvoice($this->accountFrom, $this->accountFrom, $amount);
    }

    public function wrongAmountProvider()
    {
        return [
            [0],
            [-0.01],
            [-10],
        ];
    }
}
