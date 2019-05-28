<?php

namespace viktorprogger\Accounting\Interfaces\Services;

interface DBInterface
{
    public function beginTransaction();

    public function commit();

    public function rollback();
}
