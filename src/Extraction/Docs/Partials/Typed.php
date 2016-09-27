<?php


namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait Typed {
    /** @var string|null */
    public $type = null;

    /** @var string|null */
    public $defaultValue = null;

    public function setType(string $type) {
        $this->type = $type ?: null;
    }

    public function setDefaultValue(string $defaultValue = null) {
        $this->defaultValue = $defaultValue;
    }
}