<?php

namespace Kelunik\DaaS\Extraction;

use Kelunik\DaaS\Extraction\Docs\FunctionDoc;
use Kelunik\DaaS\Extraction\Docs\ParameterDoc;
use Kelunik\DaaS\Extraction\Resolver\NameResolver;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\PrettyPrinter;
use function Kelunik\DaaS\getNamespace;

class FunctionExtractor implements NodeVisitor {
    private $file;
    private $functions;
    private $warnings;
    private $docBlockFactory;
    private $nameResolver;

    public function __construct(DocBlockFactory $docBlockFactory, NameResolver $nameResolver) {
        $this->docBlockFactory = $docBlockFactory;
        $this->nameResolver = $nameResolver;
    }

    public function setFile(string $file) {
        $this->file = $file;
    }

    private function reset() {
        $this->functions = [];
        $this->warnings = [];
    }

    public function beforeTraverse(array $nodes) {
        $this->reset();
    }

    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\Function_) {
            $function = (new FunctionDoc($node->name));
            $function->setNamespace(getNamespace((string) $node->namespacedName));
            $function->setReturnType((string) $node->returnType);

            $function->setFile($this->file);
            $function->setStartLine($node->getAttribute("startLine"));
            $function->setEndLine($node->getAttribute("endLine"));

            $docBlock = $this->createDocBlockFromComment($node->getDocComment(), $function->namespace);
            $function->setInternal($docBlock->hasTag("internal") || preg_match('/^_(?!_)/', $function->name));

            $paramTags = [];

            foreach ($docBlock->getTagsByName("param") as $paramTag) {
                /** @var \phpDocumentor\Reflection\DocBlock\Tags\Param $paramTag */
                $paramTags[$paramTag->getVariableName()] = $paramTag;
            }

            $function->setSummary($docBlock->getSummary());
            $function->setDescription($docBlock->getDescription()->render());

            if ($docBlock->hasTag("return")) {
                // TODO: Check whether types are compatible if both are present
                /** @var Return_[] $returnTags */
                $returnTags = $docBlock->getTagsByName("return");

                if (count($returnTags) > 1) {
                    $this->warnings["{$function->namespace}{$function->name}"][] = "Multiple return tags";
                }

                $function->setReturnType($returnTags[0]->getType());
            }

            foreach ($node->getParams() as $paramNode) {
                $param = (new ParameterDoc($paramNode->name));
                $param->setType((string) $paramNode->type);
                $param->setByRef($paramNode->byRef);
                $param->setVariadic($paramNode->variadic);
                $param->setDefaultValue($paramNode->default ? (new PrettyPrinter\Standard)->prettyPrintExpr($paramNode->default) : null);

                if (isset($paramTags[$param->name])) {
                    /** @var Param $paramDoc */
                    $paramDoc = $paramTags[$param->name];

                    $param->setDescription($paramDoc->getDescription()->render());
                    $param->setType((string) $paramDoc->getType());

                    // TODO: Check whether types are compatible if both are present
                } else {
                    $this->warnings["{$function->namespace}{$function->name}"][] = "Documentation for parameter \${$param->name} missing";
                }

                $function->addParameter($param);
            }

            $this->functions[] = $function;
        }
    }

    public function leaveNode(Node $node) {
        return $node;
    }

    public function afterTraverse(array $nodes) {
        return $nodes;
    }

    /**
     * @return FunctionDoc[]
     */
    public function getFunctions(): array {
        return $this->functions;
    }

    public function getWarnings(): array {
        return $this->warnings;
    }

    private function createDocBlockFromComment(Doc $docComment = null, string $namespace) {
        $context = new Context($namespace, $this->nameResolver->getClassAliases());

        if ($docComment === null) {
            return $this->docBlockFactory->create("/***/", $context);
        }

        try {
            return $this->docBlockFactory->create($docComment->getReformattedText(), $context);
        } catch (\InvalidArgumentException $e) {
            return $this->docBlockFactory->create("/***/", $context);
        }
    }
}
