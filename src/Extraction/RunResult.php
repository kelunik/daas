<?php

namespace Kelunik\DaaS\Extraction;

class RunResult {
    private $classList;
    private $functionList;

    public function __construct(array $classList, array $functionList) {
        $this->classList = $classList;
        $this->functionList = $functionList;
    }

    public function getClassList(): array {
        return $this->classList;
    }

    public function getFunctionList(): array {
        return $this->functionList;
    }
}