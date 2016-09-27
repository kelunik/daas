<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

trait Summary {
    public $summary = "";

    public function setSummary(string $summary) {
        $this->summary = $summary;
    }
}