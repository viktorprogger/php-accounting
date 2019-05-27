<?php

use miolae\Accounting\Interfaces\DTO\AccountInterface;
use miolae\Accounting\Interfaces\DTO\InvoiceInterface;
use miolae\Accounting\Interfaces\DTO\TransactionInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Module;
use miolae\Accounting\Services\AccountService;
use miolae\Accounting\Services\InvoiceService;
use miolae\Accounting\Services\TransactionService;
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

    /** @var InvoiceInterface|ObjectProphecy */
    protected $invoice;

    /** @var InvoiceService|ObjectProphecy $DI */
    protected $invoiceService;

    /** @var TransactionInterface|ObjectProphecy */
    protected $transaction;

    /** @var TransactionService|ObjectProphecy */
    protected $transactionService;

    /** @var AccountService|AccountInterface */
    protected $accountFrom;

    protected function setUp()
    {
        parent::setUp();

        /** @var ServiceContainerInterface|ObjectProphecy $DI */
        $this->DI = $this->prophesize(ServiceContainerInterface::class);
        $this->DB = $this->prophesize(DBInterface::class);

        $this->transaction = $this->prophesize(TransactionInterface::class);
        $this->transactionService = $this->prophesize(TransactionService::class);
        $this->transactionService->getModel()->willReturn($this->transaction->reveal());

        $this->invoice = $this->prophesize(InvoiceInterface::class);
        $this->accountFrom = $this->prophesize(AccountService::class);

        $this->invoice->getStateHold()->willReturn(self::STATE_HOLD);
        $this->invoice->getAmount()->willReturn(self::AMOUNT);

        $this->invoiceService = $this->prophesize(InvoiceService::class);
        $this->invoiceService->loadInvoice()->willReturn($this->invoice->reveal());
        $this->invoiceService->getModel()->willReturn($this->invoice->reveal());

        $this->DI->getDB()->willReturn($this->DB->reveal());
        $this->DI->getTransactionService()->willReturn($this->transactionService->reveal());
        $this->DI->getInvoiceService($this->invoice->reveal())->willReturn($this->invoiceService->reveal());
        $this->DI->getAccountService(new TypeToken(AccountInterface::class))->willReturn($this->accountFrom->reveal());
    }

    public function testOk()
    {
        $this->invoice->setStateHold()->shouldBeCalledTimes(1);

        $this->invoiceService->canHold()->willReturn(true);
        $this->invoiceService->getAccountFrom()->shouldBeCalledTimes(1);
        $this->invoiceService->saveModel()->shouldBeCalledTimes(1);
        $this->invoiceService->loadInvoice()->shouldBeCalledTimes(1);

        $this->transactionService->createNewTransaction($this->invoice->reveal(), self::STATE_HOLD)->shouldBeCalledTimes(1);
        $this->transactionService->saveModel()->shouldBeCalledTimes(2);
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
