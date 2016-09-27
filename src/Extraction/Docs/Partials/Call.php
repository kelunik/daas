<?php


namespace Kelunik\DaaS\Extraction\Docs\Partials;

use Kelunik\DaaS\Extraction\Docs\ParameterDoc;

trait Call {
    use Summary;
    use Internal;
    use FileReference;

    /** @var ParameterDoc[] */
    public $parameters = [];

    /** @var string|null */
    public $returnType = null;

    /** @var bool */
    public $returnByRef = false;

    public function addParameter(ParameterDoc $parameter) {
        $this->parameters[$parameter->name] = $parameter;
    }

    public function setReturnType(string $returnType) {
        $this->returnType = $returnType ?: null;
    }

    public function setReturnByRef(bool $returnByDef) {
        $this->returnByRef = $returnByDef;
    }
}