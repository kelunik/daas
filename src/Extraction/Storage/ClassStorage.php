<?php

namespace Kelunik\DaaS\Extraction\Storage;

use Kelunik\DaaS\Extraction\Docs\ClassDoc;

class ClassStorage {
    private $fileStorage;

    public function __construct(FileStorage $fileStorage) {
        $this->fileStorage = $fileStorage;
    }

    public function save(ClassDoc $classDoc) {
        $fqn = "\\" . ltrim($classDoc->namespace, "\\") . $classDoc->name;
        $this->fileStorage->save($classDoc, $fqn);
    }

    public function load(string $fqn): ClassDoc {
        $classDoc = $this->fileStorage->load($fqn);

        if (!$classDoc instanceof ClassDoc) {
            throw new StorageException("Wrong class loaded: " . get_class($classDoc));
        }

        return $classDoc;
    }
}