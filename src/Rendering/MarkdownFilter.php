<?php

namespace Kelunik\DaaS\Rendering;

use Twig_SimpleFilter as TwigFilter;

class MarkdownFilter extends TwigFilter {
    public function __construct(Markdown $markdown) {
        parent::__construct("markdown", static function(string $content) use ($markdown) {
            // TODO: Filter HTML and make sure it's valid.
            return $markdown->render($content);
        }, [
            "is_safe" => ["html"],
        ]);
    }
}