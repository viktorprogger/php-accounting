<?php

namespace miolae\Accounting\Traits;

/**
 * Trait ModelMixinTrait
 *
 * @package miolae\Accounting\Traits
 *
 * @property-read $model
 */
trait ModelMixinTrait
{
    public function __call($name, $arguments)
    {
        return $this->model->$name($arguments);
    }
}
