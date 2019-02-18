<?php

use miolae\Accounting\Decorators\InvoiceDecorator;
use miolae\Accounting\Decorators\TransactionDecorator;
use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Module;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleCancelTest extends TestCase
{
    /** @var ServiceContainerInterface|MockObject */
    protected $DI;
    /** @var DBInterface|MockObject */
    protected $db;

    protected function setUp()
    {
        parent::setUp();
        $this->DI = $this->createMock(ServiceContainerInterface::class);
        $this->db = $this->createMock(DBInterface::class);

        $transactionDecorator = $this->createMock(TransactionDecorator::class);

        $this->DI->method('getDB')->willReturn($this->db);
        $this->DI->method('getTransactionDecorator')->willReturn($transactionDecorator);
    }

    public function testOk(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(false);

        $invoiceDecorator = $this->createMock(InvoiceDecorator::class);
        $invoiceDecorator->method('canCancel')->willReturn(true);
        $invoiceDecorator->method('getModel')->willReturn($invoice);

        $this->DI->method('getInvoiceDecorator')->willReturn($invoiceDecorator);

        $this->db->expects($this->once())->method('commit');
        $invoiceDecorator->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        $module->cancel($invoice);
    }

    public function testOkHold(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(true);

        $invoiceDecorator = $this->createMock(InvoiceDecorator::class);
        $invoiceDecorator->method('canCancel')->willReturn(true);
        $invoiceDecorator->method('getModel')->willReturn($invoice);
        $invoiceDecorator->expects($this->once())->method('loadInvoice');

        $this->DI->method('getInvoiceDecorator')->willReturn($invoiceDecorator);

        $module = new Module($this->DI);
        $module->cancel($invoice);
    }

    public function testWrongState(): void
    {
        $invoiceDecorator = $this->createMock(InvoiceDecorator::class);
        $invoiceDecorator->method('canCancel')->willReturn(false);

        $this->DI->method('getInvoiceDecorator')->willReturn($invoiceDecorator);

        $this->expectException(WrongStateException::class);

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }

    public function testException(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(true);

        /** @var ExceptionInterface $exception */
        $exception = $this->createMock(ExceptionInterface::class);

        $invoiceDecorator = $this->createMock(InvoiceDecorator::class);
        $invoiceDecorator->method('canCancel')->willReturn(true);
        /** @noinspection PhpParamsInspection */
        $invoiceDecorator->method('saveModel')->willThrowException($exception);

        $this->DI->method('getInvoiceDecorator')->willReturn($invoiceDecorator);

        $this->db->expects($this->once())->method('rollback');
        $invoiceDecorator->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        $module->cancel($invoice);
    }
}
