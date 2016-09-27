<?php

namespace Kelunik\DaaS\Rendering;

use Aerys\Request;

class TwigContext {
    private $data;
    private $context;

    private function __construct(array $data = [], array $context = []) {
        $this->data = $data;
        $this->context = $context;
    }

    public function toArray() {
        return [
            "data" => $this->data,
            "context" => $this->context
        ];
    }

    public static function fromRequest(Request $request, array $data = []) {
        return new self($data, [
            "connectionInfo" => $request->getConnectionInfo(),
        ]);
    }
}