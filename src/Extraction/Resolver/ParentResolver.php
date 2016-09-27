<?php

namespace Kelunik\DaaS\Extraction\Resolver;

use Kelunik\DaaS\Extraction\Storage\ClassStorage;
use Psr\Log\LoggerInterface as PsrLogger;

class ParentResolver extends ClassReferenceResolver {
    public function __construct(PsrLogger $logger, ClassStorage $classStorage) {
        parent::__construct($logger, $classStorage, "parents");
    }
}