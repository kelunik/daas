<?php

namespace Kelunik\DaaS\Extraction\Docs;

class MethodDoc extends BaseDoc {
    use Partials\Member;
    use Partials\Call;

    /** @var bool */
    public $final = false;

    /** @var bool */
    public $abstract = false;

    public function setFinal(bool $final) {
        $this->final = $final;
    }

    public final function setAbstract(bool $abstract) {
        $this->abstract = $abstract;
    }
}