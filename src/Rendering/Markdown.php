<?php

namespace Kelunik\DaaS\Rendering;

use League\CommonMark\CommonMarkConverter;

class Markdown {
    private $commonMarkConverter;

    public function __construct(CommonMarkConverter $commonMarkConverter) {
        $this->commonMarkConverter = $commonMarkConverter;
    }

    public function render(string $markdown) {
        return $this->commonMarkConverter->convertToHtml($markdown);
    }
}