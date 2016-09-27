<?php


namespace Kelunik\DaaS\Extraction;

use Exception;
use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use Kelunik\DaaS\Extraction\Docs\MethodDoc;
use Kelunik\DaaS\Extraction\Docs\ParameterDoc;
use Kelunik\DaaS\Extraction\Docs\PropertyDoc;
use Kelunik\DaaS\Extraction\Resolver\NameResolver;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitor;
use PhpParser\PrettyPrinter;
use function Kelunik\DaaS\getNamespace;
use function Kelunik\DaaS\getVisibilityFromType;

class ClassExtractor implements NodeVisitor {
    /** @var string */
    private $file;

    /** @var ClassDoc[] */
    private $classes;

    /** @var string[] */
    private $warnings;

    /** @var ClassDoc */
    private $currentClass;

    /** @var DocBlockFactory */
    private $docBlockFactory;

    /** @var NameResolver */
    private $nameResolver;

    public function __construct(DocBlockFactory $docBlockFactory, NameResolver $nameResolver) {
        $this->docBlockFactory = $docBlockFactory;
        $this->nameResolver = $nameResolver;
    }

    public function setFile(string $file) {
        $this->file = $file;
    }

    private function reset() {
        $this->classes = [];
        $this->warnings = [];
        $this->currentClass = null;
    }

    public function beforeTraverse(array $nodes) {
        $this->reset();
    }

    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassLike && isset($node->namespacedName)) {
            $this->currentClass = $this->createClassFromNode($node);
        } else if ($this->currentClass && $node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $property = $this->createPropertyFromNode($node, $prop);
                $this->currentClass->properties[$property->name] = $property;
            }
        } else if ($this->currentClass && $node instanceof Node\Stmt\ClassMethod) {
            $method = $this->createMethodFromNode($node);
            $this->currentClass->methods[$method->name] = $method;
        } else if ($this->currentClass && $node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->currentClass->addTrait("\\" . (string) $trait);
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                    foreach ($adaptation->insteadof as $insteadof) {
                        $this->currentClass->addTraitBlacklistEntry("\\" . (string) $insteadof, $adaptation->method);
                    }
                } elseif ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                    $visibility = $adaptation->newModifier ? getVisibilityFromType($adaptation->newModifier) : null;
                    $this->currentClass->addTraitAlias("\\" . (string) $adaptation->trait, $adaptation->method, $visibility, $adaptation->newName);
                }
            }
        }
    }

    public function leaveNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassLike && isset($node->namespacedName)) {
            $this->classes[] = $this->currentClass;
            $this->currentClass = null;
        }
    }

    public function afterTraverse(array $nodes) {
        $this->currentClass = null;
    }

    private function createClassFromNode(Node\Stmt\ClassLike $node) {
        /** @var Node\Stmt\Class_ $node */
        $class = new ClassDoc($node->name);
        $class->setNamespace(getNamespace((string) $node->namespacedName));

        $docBlock = $this->createDocBlockFromComment($node->getDocComment(), $class->namespace);

        $class->setDescription($docBlock->getDescription()->render());
        $class->setSummary($docBlock->getSummary());

        if (isset($node->extends)) {
            foreach (is_object($node->extends) ? [$node->extends] : $node->extends as $parent) {
                $class->addParent((string) $parent);
            }
        }

        if (isset($node->implements)) {
            foreach ($node->implements as $interface) {
                $class->addInterface((string) $interface);
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            $class->setClassType("class");
        } else if ($node instanceof Node\Stmt\Interface_) {
            $class->setClassType("interface");
        } else if ($node instanceof Node\Stmt\Trait_) {
            $class->setClassType("trait");
        } else {
            throw new Exception("Unknown classlike type: " . get_class($node));
        }

        $class->setInternal($docBlock->hasTag("internal"));

        $class->setFile($this->file);
        $class->setStartLine($node->getAttribute("startLine"));
        $class->setEndLine($node->getAttribute("endLine"));

        return $class;
    }

    private function createPropertyFromNode(Node\Stmt\Property $node, Node\Stmt\PropertyProperty $prop) {
        $property = new PropertyDoc($prop->name);

        $docBlock = $this->createDocBlockFromComment($node->getDocComment(), $this->currentClass->namespace);

        $property->setSummary($docBlock->getSummary());
        $property->setDescription($docBlock->getDescription()->render());

        foreach ($docBlock->getTagsByName("var") as $var) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Var_ $var */
            if ($var->getVariableName() === "" || $var->getVariableName() === $property->name) {
                $property->setDescription($property->description ? $var->getDescription()->render() : PHP_EOL . PHP_EOL . $var->getDescription()->render());
                $property->setType((string) $var->getType());
            }
        }

        $property->setInternal($docBlock->hasTag("internal"));

        $property->setFile($this->file);
        $property->setStartLine($node->getAttribute("startLine"));
        $property->setEndLine($node->getAttribute("endLine"));

        $property->setDefiningClass($this->currentClass->namespace . $this->currentClass->name);
        $property->setDefaultValue($prop->default ? (new PrettyPrinter\Standard)->prettyPrintExpr($prop->default) : null);
        $property->setVisibility(getVisibilityFromType($node->type));
        $property->setStatic($node->isStatic());

        return $property;
    }

    private function createMethodFromNode(Node\Stmt\ClassMethod $node) {
        $method = new MethodDoc($node->name);

        $method->setFile($this->file);
        $method->setStartLine($node->getAttribute("startLine"));
        $method->setEndLine($node->getAttribute("endLine"));

        $method->setDefiningClass($this->currentClass->namespace . $this->currentClass->name);
        $method->setVisibility(getVisibilityFromType($node->type));
        $method->setStatic($node->isStatic());
        $method->setFinal($node->isFinal());
        $method->setAbstract($node->isAbstract());
        $method->setReturnType((string) $node->getReturnType());

        $docBlock = $this->createDocBlockFromComment($node->getDocComment(), $this->currentClass->namespace);

        $paramTags = [];

        foreach ($docBlock->getTagsByName("param") as $paramTag) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Param $paramTag */
            $paramTags[$paramTag->getVariableName()] = $paramTag;
        }

        if ($docBlock->hasTag("return")) {
            // TODO: Check whether types are compatible if both are present
            /** @var Return_[] $returnTags */
            $returnTags = $docBlock->getTagsByName("return");

            if (count($returnTags) > 1) {
                $this->warnings["{$this->currentClass->namespace}{$this->currentClass->name}"][$method->name][] = "Multiple return tags";
            }

            $method->setReturnType($returnTags[0]->getType());
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
                $this->warnings["{$this->currentClass->namespace}{$this->currentClass->name}"][$method->name][] = "Documentation for parameter \${$param->name} missing";
            }

            $method->addParameter($param);
        }

        $method->setInternal($docBlock->hasTag("internal"));
        $method->setSummary($docBlock->getSummary());
        $method->setDescription($docBlock->getDescription()->render());

        return $method;
    }

    /**
     * @return ClassDoc[]
     */
    public function getClasses(): array {
        return $this->classes;
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