<?php


namespace Kelunik\DaaS\Extraction\Storage;

use Kelunik\DaaS\Extraction\Docs\FunctionDoc;

class FunctionStorage {
    private $fileStorage;

    public function __construct(FileStorage $fileStorage) {
        $this->fileStorage = $fileStorage;
    }

    public function save(FunctionDoc $functionDoc) {
        $fqn = "\\" . ltrim($functionDoc->namespace, "\\") . $functionDoc->name;
        $this->fileStorage->save($functionDoc, $fqn);
    }

    public function load(string $fqn): FunctionDoc {
        $functionDoc = $this->fileStorage->load($fqn);

        if (!$functionDoc instanceof FunctionDoc) {
            throw new StorageException("Wrong class loaded: " . get_class($functionDoc));
        }

        return $functionDoc;
    }
}