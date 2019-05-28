<?php

use viktorprogger\Accounting\Services\InvoiceService;
use viktorprogger\Accounting\Services\TransactionService;
use viktorprogger\Accounting\Exceptions\WrongStateException;
use viktorprogger\Accounting\Interfaces\ExceptionInterface;
use viktorprogger\Accounting\Interfaces\DTO\InvoiceInterface;
use viktorprogger\Accounting\Interfaces\ServiceContainerInterface;
use viktorprogger\Accounting\Interfaces\Services\DBInterface;
use viktorprogger\Accounting\Module;
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

        $transactionService = $this->createMock(TransactionService::class);

        $this->DI->method('getDB')->willReturn($this->db);
        $this->DI->method('getTransactionService')->willReturn($transactionService);
    }

    public function testOk(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(false);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('getModel')->willReturn($invoice);

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->db->expects($this->once())->method('commit');
        $invoiceService->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        /** @noinspection PhpParamsInspection */
        $module->cancel($invoice);
    }

    public function testOkHold(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(true);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('getModel')->willReturn($invoice);
        $invoiceService->expects($this->once())->method('loadInvoice');

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $module = new Module($this->DI);
        /** @noinspection PhpParamsInspection */
        $module->cancel($invoice);
    }

    public function testWrongState(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(false);

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->expectException(WrongStateException::class);

        $module = new Module($this->DI);
        /** @noinspection PhpParamsInspection */
        $module->cancel($this->createMock(InvoiceInterface::class));
    }

    public function testException(): void
    {
        $invoice = $this->createMock(InvoiceInterface::class);
        $invoice->method('isStateHold')->willReturn(true);

        /** @var ExceptionInterface $exception */
        $exception = $this->createMock(ExceptionInterface::class);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        /** @noinspection PhpParamsInspection */
        $invoiceService->method('saveModel')->willThrowException($exception);

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->db->expects($this->once())->method('rollback');
        $invoiceService->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        /** @noinspection PhpParamsInspection */
        $module->cancel($invoice);
    }
}
