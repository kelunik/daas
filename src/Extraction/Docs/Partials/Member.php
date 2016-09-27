<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait Member {
    /** @var string */
    public $definingClass = null;

    /** @var string */
    public $visibility = null;

    /** @var bool */
    public $static = false;

    public function setDefiningClass(string $definingClass) {
        $this->definingClass = $definingClass;
    }

    public function setVisibility(string $visibility) {
        $this->visibility = $visibility;
    }

    public function setStatic(bool $static) {
        $this->static = $static;
    }
}