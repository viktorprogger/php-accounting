<?php

namespace miolae\Accounting\Interfaces\Services;

use miolae\Accounting\Interfaces\Models\TransactionInterface as TransactionModel;

interface TransactionInterface
{
    public function __construct(TransactionModel $transaction);

    public function createNewTransaction($invoice): void;

    public function saveModel();

    public function getTransaction(): TransactionModel;
}
