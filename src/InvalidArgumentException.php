<?php

namespace Kelunik\DaaS;

use Exception;

class InvalidArgumentException extends Exception {
    public function __construct(string $reason = null) {
        if ($reason) {
            parent::__construct("Invalid argument supplied: " . $reason, 0, null);
        } else {
            parent::__construct("Invalid argument supplied", 0, null);
        }
    }
}