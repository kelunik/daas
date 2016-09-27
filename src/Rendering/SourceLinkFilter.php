<?php


namespace Kelunik\DaaS\Rendering;

use Twig_SimpleFilter as TwigFilter;

class SourceLinkFilter extends TwigFilter {
    public function __construct() {
        parent::__construct("sourcelink", function ($context, $element, $label = null) {
            return $this->link($context["data"]["package_blob_url"] . "/" . $element["file"] . "#L" . $element["startLine"] . "-L" . $element["endLine"], $label);
        }, [
            "is_safe" => ["html"],
            "needs_context" => true,
        ]);
    }

    private function link(string $href = null, string $text = null): string {
        if ($href === null) {
            return '<span>' . htmlspecialchars($text ?? $href, ENT_COMPAT, "utf-8") . '</span>';
        }

        return '<a href="' . htmlspecialchars($href, ENT_COMPAT, "utf-8") . '">' . htmlspecialchars($text ?? $href, ENT_COMPAT, "utf-8") . '</a>';
    }
}