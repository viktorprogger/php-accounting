<?php

namespace miolae\Accounting\Interfaces\Services;

interface DBInterface
{
    public function beginTransaction();

    public function commit();

    public function rollback();
}
