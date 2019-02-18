<?php

namespace miolae\Accounting\Interfaces\Models;

interface InvoiceInterface
{
    public function setAmount(float $amount);

    public function getAmount(): float;

    public function getState();

    public function setStateCreated(): void;

    public function setStateHold(): void;

    public function setStateTransacted(): void;

    public function setStateSuccess(): void;

    public function setStateCanceled(): void;

    public function isStateCreated(): bool;

    public function isStateHold(): bool;

    public function isStateTransacted(): bool;

    public function isStateSuccess(): bool;

    public function isStateCanceled(): bool;

    public function getStateCreated();

    public function getStateHold();

    public function getStateTransacted();

    public function getStateSuccess();

    public function getStateCanceled();
}
