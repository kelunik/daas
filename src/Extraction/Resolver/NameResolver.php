<?php

namespace Kelunik\DaaS\Extraction\Resolver;

use PhpParser\Node\Stmt\Use_;

class NameResolver extends \PhpParser\NodeVisitor\NameResolver {
    public function getClassAliases(): array {
        return array_map(function ($alias) {
            return (string) $alias;
        }, $this->aliases[Use_::TYPE_NORMAL]);
    }
}