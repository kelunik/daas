<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait FileReference {
    /** @var string */
    public $file = "";

    /** @var int */
    public $startLine = 0;

    /** @var int */
    public $endLine = 0;

    public function setFile(string $file) {
        $this->file = $file;
    }

    public function setStartLine(int $startLine) {
        $this->startLine = $startLine;
    }

    public function setEndLine(int $endLine) {
        $this->endLine = $endLine;
    }
}