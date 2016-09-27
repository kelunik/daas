<?php


namespace Kelunik\DaaS\Download;

use Exception;

class DownloadException extends Exception {
    public function __construct(string $message, Exception $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}