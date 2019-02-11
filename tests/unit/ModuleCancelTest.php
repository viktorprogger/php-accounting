<?php

use miolae\Accounting\Exceptions\WrongStateException;
use miolae\Accounting\Interfaces\ExceptionInterface;
use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;
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

        $this->DI->method('getDB')->willReturn($this->db);
    }

    public function testOk(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('isStateHold')->willReturn(false);
        $invoiceService->method('getInvoice')->willReturn($this->createMock(InvoiceInterface::class));

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->db->expects($this->once())->method('commit');
        $invoiceService->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }

    public function testOkHold(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('isStateHold')->willReturn(true);
        $invoiceService->method('getInvoice')->willReturn($this->createMock(InvoiceInterface::class));
        $invoiceService->expects($this->once())->method('loadInvoice');

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }

    public function testWrongState(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(false);

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->expectException(WrongStateException::class);

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }

    public function testException(): void
    {
        /** @var ExceptionInterface $exception */
        $exception = $this->createMock(ExceptionInterface::class);

        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('isStateHold')->willReturn(false);
        /** @noinspection PhpParamsInspection */
        $invoiceService->method('saveModel')->willThrowException($exception);

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $this->db->expects($this->once())->method('rollback');
        $invoiceService->expects($this->once())->method('loadInvoice');

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }
}
