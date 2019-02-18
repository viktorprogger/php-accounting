<?php

namespace miolae\Accounting\Interfaces\Models;

interface InvoiceInterface
{
    public function setAmount(float $amount);

    public function setStateCreated();

    public function setStateHold();

    public function setStateSuccess();

    public function setStateCanceled();

    public function isStateCreated(): bool;

    public function isStateHold(): bool;

    public function isStateSuccess(): bool;

    public function isStateCanceled(): bool;

    public function getAmount(): float;
}
