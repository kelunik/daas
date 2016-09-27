<?php


namespace Kelunik\DaaS\Extraction\Storage;

use function Kelunik\DaaS\base64url_encode;
use Kelunik\DaaS\Extraction\Docs\BaseDoc;
use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use Kelunik\DaaS\Extraction\Docs\FunctionDoc;
use Kelunik\DaaS\Extraction\Docs\MethodDoc;
use Kelunik\DaaS\Extraction\Docs\ParameterDoc;
use Kelunik\DaaS\Extraction\Docs\PropertyDoc;

class FileStorage {
    private $baseDir;

    public function __construct(string $baseDir) {
        $this->baseDir = $baseDir;
    }

    public function save(BaseDoc $doc, string $fqn) {
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }

        $file = base64url_encode(strtolower($fqn)) . ".txt";
        file_put_contents($this->baseDir . "/" . $file, serialize($doc));
    }

    public function load(string $fqn): BaseDoc {
        $fqn = "\\" . ltrim($fqn, "\\");
        $file = $this->baseDir . "/" . base64url_encode(strtolower($fqn)) . ".txt";

        if (!file_exists($file)) {
            throw new FileNotFoundException("Could not load '{$fqn}' from '{$this->baseDir}'");
        } else if (!is_readable($file)) {
            throw new StorageException("Missing read permission to read '{$fqn}' from '{$this->baseDir}'");
        }

        $content = @file_get_contents($file);

        if ($content === false) {
            throw new StorageException("Couldn't load '{$fqn}' from '{$this->baseDir}'");
        }

        $doc = @unserialize($content, [
            "allowed_classes" => [
                FunctionDoc::class,
                ClassDoc::class,
                MethodDoc::class,
                ParameterDoc::class,
                PropertyDoc::class,
            ],
        ]);

        if ($doc === false) {
            throw new StorageException("Error during unserialization of '{$fqn}'");
        }

        return $doc;
    }
}