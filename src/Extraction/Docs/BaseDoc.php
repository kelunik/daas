<?php

namespace Kelunik\DaaS\Extraction\Docs;

abstract class BaseDoc {
    /** @var string */
    public $name = "";

    /** @var string */
    public $description = "";

    public function __construct(string $name, string $description = "") {
        $this->name = $name;
        $this->description = $description;
    }

    public function setDescription(string $description) {
        $this->description = $description;
    }
}