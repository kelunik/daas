<?php


namespace Kelunik\DaaS\Extraction\Docs\Partials;


trait Namespaced {
    /** @var string */
    public $namespace = "";

    public function setNamespace(string $namespace) {
        $this->namespace = ltrim($namespace, "\\");
    }
}