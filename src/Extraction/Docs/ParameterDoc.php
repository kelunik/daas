<?php

namespace Kelunik\DaaS\Extraction\Docs;

class ParameterDoc extends BaseDoc {
    use Partials\Typed;

    /** @var bool */
    public $byRef = false;

    /** @var bool */
    public $variadic = false;

    public function setByRef(bool $byRef) {
        $this->byRef = $byRef;
    }

    public function setVariadic(bool $variadic) {
        $this->variadic = $variadic;
    }
}