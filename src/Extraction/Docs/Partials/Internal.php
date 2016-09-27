<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait Internal {
    /** @var bool */
    public $internal = false;

    public function setInternal(bool $internal) {
        $this->internal = $internal;
    }
}