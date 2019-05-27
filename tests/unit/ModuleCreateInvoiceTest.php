<?php

use miolae\Accounting\Services\InvoiceService;
use miolae\Accounting\Exceptions\SameAccountException;
use miolae\Accounting\Exceptions\WrongAmountException;
use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Module;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ModuleCreateInvoiceTest extends TestCase
{
    /** @var ServiceContainerInterface|ObjectProphecy $DI */
    protected $DI;
    /** @var InvoiceService|ObjectProphecy $DI */
    protected $invoiceService;
    /** @var AccountInterface */
    protected $accountFrom;
    /** @var AccountInterface */
    protected $accountTo;

    protected function setUp()
    {
        parent::setUp();

        /** @var ServiceContainerInterface|ObjectProphecy $DI */
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->invoiceService = $this->prophesize(InvoiceService::class);
        $this->invoiceService->loadInvoice()->willReturn($this->prophesize(InvoiceInterface::class)->reveal());
        $this->DI->getInvoiceService()->willReturn($this->invoiceService->reveal());

        $this->accountFrom = $this->prophesize(AccountInterface::class)->reveal();
        $this->accountTo = $this->prophesize(AccountInterface::class)->reveal();
    }

    public function testOk(): void
    {
        $amount = 0.01;
        $this->invoiceService->createNewInvoice($this->accountFrom, $this->accountTo, $amount)->shouldBeCalledTimes(1);
        $this->invoiceService->saveModel()->shouldBeCalledTimes(1);

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
        $this->invoiceService->createNewInvoice($this->accountFrom, $this->accountTo, $amount)->shouldNotBeCalled();

        (new Module($this->DI->reveal()))->createInvoice($this->accountFrom, $this->accountTo, $amount);
    }

    public function testWrongAccount()
    {
        $amount = 1;
        $this->expectException(SameAccountException::class);
        $this->invoiceService->createNewInvoice($this->accountFrom, $this->accountFrom, $amount)->shouldNotBeCalled();

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
