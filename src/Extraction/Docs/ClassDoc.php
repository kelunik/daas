<?php

namespace Kelunik\DaaS\Extraction\Docs;

class ClassDoc extends BaseDoc {
    use Partials\PackageInfo;
    use Partials\FileReference;
    use Partials\Namespaced;
    use Partials\Internal;
    use Partials\Summary;

    /** @var string */
    public $classType = null;

    /** @var string[] */
    public $parents = [];

    /** @var string[] */
    public $interfaces = [];

    /** @var string[] */
    public $traits = [];

    /** @var string[] */
    public $traitBlacklist = [];

    /** @var string[] */
    public $traitAliases = [];

    /** @var PropertyDoc[] */
    public $properties = [];

    /** @var MethodDoc[] */
    public $methods = [];

    public function setClassType(string $classType) {
        $this->classType = $classType;
    }

    public function addParent(string $parent) {
        $this->parents[] = $parent;
    }

    public function addInterface(string $interface) {
        $this->interfaces[] = $interface;
    }

    public function addTrait(string $trait) {
        $this->traits[] = $trait;
    }

    public function addTraitBlacklistEntry(string $class, string $method) {
        $this->traitBlacklist[$class][$method] = true;
    }

    public function addTraitAlias(string $class, string $method, string $visibility = null, string $alias = null) {
        $this->traitAliases[$class][$method][] = [$alias, $visibility];
    }

    public function addProperty(PropertyDoc $property) {
        $this->properties[$property->name] = $property;
    }

    public function addMethod(MethodDoc $method) {
        $this->methods[$method->name] = $method;
    }

    public function mergeProperties(ClassDoc $class) {
        foreach ($class->properties as $property) {
            if (isset($this->properties[$property->name])) {
                continue;
            }

            $isTrait = $class->classType === "trait";

            // Private properties are not inherited, except for those in traits,
            // as they do not use inheritance but copy and paste instead.
            if (!$isTrait && $property->visibility === "private") {
                continue;
            }

            $this->addProperty(clone $property);
        }
    }

    public function mergeMethods(ClassDoc $class) {
        foreach ($class->methods as $method) {
            if ($class->classType === "trait") {
                // Exclude if insteadof is used.
                if (isset($this->traitBlacklist[$class->namespace . $class->name][$method->name])) {
                    continue;
                }

                if (isset($this->traitAliases[$class->namespace . $class->name][$method->name])) {
                    foreach ($this->traitAliases[$class->namespace . $class->name][$method->name] as list($alias, $visibility)) {
                        if (isset($this->methods[$alias])) {
                            continue;
                        }

                        $aliasMethod = clone $method;
                        $aliasMethod->name = $alias ?? $aliasMethod->name;
                        $aliasMethod->visibility = $visibility ?? $aliasMethod->visibility;

                        $this->addMethod($aliasMethod);
                    }
                }

                // Don't exclude private methods for traits!
                // Don't add multiple times if it's already added because of a visibility change.
                if (isset($this->methods[$method->name])) {
                    continue;
                }

                $this->addMethod(clone $method);
            } else {
                if ($method->visibility === "private") {
                    continue;
                }

                if (!isset($this->methods[$method->name])) {
                    $this->addMethod(clone $method);
                }
            }

            if (isset($this->methods[$method->name])) {
                $toMethod = $this->methods[$method->name];

                // Inherit description
                if (preg_match("~\\{@inheritdoc\\}~i", $toMethod->summary)) {
                    $toMethod->setSummary(preg_replace("~\\{@inheritdoc\\}~i", $method->summary, $toMethod->summary));
                    $toMethod->setDescription($method->description . PHP_EOL . PHP_EOL . $toMethod->description);
                } else if (preg_match("~\\{@inheritdoc\\}~i", $toMethod->description)) {
                    $toMethod->setDescription(preg_replace("~\\{@inheritdoc\\}~i", $method->description, $toMethod->description));
                } else if (!$toMethod->summary) {
                    $toMethod->setSummary($method->summary);
                    $toMethod->setDescription($method->description);
                }

                $i = 0;
                $fromParameters = array_values($method->parameters);
                $parameterRenames = [];

                // Inherit parameters by position, *not* by name!
                // Parameter names might differ between implementations, positions do not.
                foreach ($toMethod->parameters as $parameter) {
                    // Rename to automatically replace in descriptions
                    if (isset($fromParameters[$i]) && $fromParameters[$i]->name !== $parameter->name) {
                        $parameterRenames["$" . $fromParameters[$i]->name] = "$" . $parameter->name;
                    }

                    if (!$parameter->description && isset($fromParameters[$i]->description)) {
                        $parameter->setDescription($fromParameters[$i]->description);
                    }

                    if (!$parameter->type && isset($fromParameters[$i]->type)) {
                        $parameter->setType($fromParameters[$i]->type);
                    }

                    $i++;
                }

                // TODO: Inherit return type and description

                // TODO: Inherit throws

                // Rename all occurrences of parent signature parameter names to the new names.
                $fromName = array_keys($parameterRenames);
                $toName = array_values($parameterRenames);

                $toMethod->setSummary(str_replace($fromName, $toName, $toMethod->summary));
                $toMethod->setDescription(str_replace($fromName, $toName, $toMethod->description));

                foreach ($toMethod->parameters as $parameter) {
                    $parameter->setDescription(str_replace($fromName, $toName, $parameter->description));
                }

                continue;
            }
        }
    }
}