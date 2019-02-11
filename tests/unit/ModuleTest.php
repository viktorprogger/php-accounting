<?php

use miolae\Accounting\Interfaces\Models\InvoiceInterface;
use miolae\Accounting\Interfaces\ServiceContainerInterface;
use miolae\Accounting\Interfaces\Services\DBInterface;
use miolae\Accounting\Interfaces\Services\InvoiceInterface as InvoiceService;
use miolae\Accounting\Module;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    /** @var ServiceContainerInterface|MockObject */
    protected $DI;

    protected function setUp()
    {
        parent::setUp();
        $this->DI = $this->createMock(ServiceContainerInterface::class);
        $this->DI->method('getDB')->willReturn($this->createMock(DBInterface::class));
    }

    public function testCancelOk(): void
    {
        $invoiceService = $this->createMock(InvoiceService::class);
        $invoiceService->method('canCancel')->willReturn(true);
        $invoiceService->method('isStateHold')->willReturn(false);
        $invoiceService->method('getInvoice')->willReturn($this->createMock(InvoiceInterface::class));
        $invoiceService->expects($this->once())->method('loadInvoice');

        $this->DI->method('getInvoiceService')->willReturn($invoiceService);

        $module = new Module($this->DI);
        $module->cancel($this->createMock(InvoiceInterface::class));
    }
}
