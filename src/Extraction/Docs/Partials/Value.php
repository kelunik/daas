<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait Value {
    /** @var string */
    public $value;

    public function setValue(string $value) {
        $this->value = $value;
    }
}