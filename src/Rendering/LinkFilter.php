<?php


namespace Kelunik\DaaS\Rendering;

use Kelunik\DaaS\InvalidArgumentException;
use Twig_SimpleFilter as TwigFilter;

class LinkFilter extends TwigFilter {
    public function __construct() {
        parent::__construct("link", function ($context, $fqn, $type = "class") {
            if ($type === "class") {
                $types = explode("|", $fqn);
                $types = array_map(function($type) use ($context) {
                    if (\Kelunik\DaaS\isSpecialType($type)) {
                        return $this->link("https://php.net/" . urlencode($type), $type);
                    }

                    $exits = class_exists($type, false)
                        || interface_exists($type, false)
                        || trait_exists($type, false);

                    if ($exits && (new \ReflectionClass($type))->isInternal()) {
                        return $this->link("https://php.net/" . urlencode($type), ltrim($type, "\\"));
                    }

                    return $this->link("/{$context["data"]["vendor"]}/{$context["data"]["package"]}/{$context["data"]["version"]}/classes/" . str_replace("%2F", "/", urlencode(strtr(ltrim(strtolower($type), "\\"), "\\", "/"))), ltrim($type, "\\"));
                }, $types);

                return implode('<span class="type-separator">|</span>', $types);
            }

            if ($type === "function") {
                return $this->link("/{$context["data"]["vendor"]}/{$context["data"]["package"]}/{$context["data"]["version"]}/functions/" . str_replace("%2F", "/", urlencode(strtr(ltrim(strtolower($fqn), "\\"), "\\", "/"))), $fqn);
            }

            throw new InvalidArgumentException("Invalid type: '{$type}'");
        }, [
            "is_safe" => ["html"],
            "needs_context" => true,
        ]);
    }

    private function link(string $href = null, string $text = null): string {
        return '<a href="' . htmlspecialchars($href, ENT_COMPAT, "utf-8") . '">' . htmlspecialchars($text ?? $href, ENT_COMPAT, "utf-8") . '</a>';
    }
}