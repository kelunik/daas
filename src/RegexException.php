<?php

namespace Kelunik\DaaS;

use Exception;

class RegexException extends Exception {
    public function __construct(string $reason = null) {
        if ($reason) {
            parent::__construct("Regular expression matching failed: " . $reason, 0, null);
        } else {
            parent::__construct("Regular expression matching failed", 0, null);
        }
    }
}