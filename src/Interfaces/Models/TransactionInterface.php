<?php

namespace miolae\Accounting\Interfaces\Models;

interface TransactionInterface
{
    public function setStateNew(): void;

    public function setStateSuccess(): void;

    public function setStateFail(): void;

    public function setTypeHold(): void;

    public function setTypeFinish(): void;

    public function setTypeCancel(): void;
}
