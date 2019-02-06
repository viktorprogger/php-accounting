<?php

namespace miolae\Accounting\Interfaces;

interface DBInterface
{
    public function beginTransaction();

    public function commit();

    public function rollback();
}
