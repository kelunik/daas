<?php


namespace Kelunik\DaaS\Extraction\Docs;

class ClassConstantDoc extends BaseDoc {
    use Partials\FileReference;
    use Partials\Internal;

    /** @var string */
    public $definingClass = null;

    /** @var string */
    public $visibility = null;

    public function setDefiningClass(string $definingClass) {
        $this->definingClass = $definingClass;
    }

    public function setVisibility(string $visibility) {
        $this->visibility = $visibility;
    }
}