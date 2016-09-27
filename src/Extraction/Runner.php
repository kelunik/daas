<?php

namespace Kelunik\DaaS\Extraction;

use Kelunik\DaaS\Extraction\Resolver\NameResolver;
use Kelunik\DaaS\Extraction\Storage\ClassStorage;
use Kelunik\DaaS\Extraction\Storage\FunctionStorage;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Error as ParseError;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use Psr\Log\LoggerInterface as PsrLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Runner {
    private $finder;
    private $classStorage;
    private $classExtractor;
    private $functionStorage;
    private $functionExtractor;
    private $nameResolver;
    private $typeResolver;
    private $parser;
    private $nodeTraverser;
    private $logger;

    public function __construct(
        Finder $finder,
        ClassStorage $classStorage,
        FunctionStorage $functionStorage,
        ClassExtractor $classExtractor,
        FunctionExtractor $functionExtractor,
        NameResolver $nameResolver,
        TypeResolver $typeResolver,
        Parser $parser,
        NodeTraverser $nodeTraverser,
        PsrLogger $logger
    ) {
        $this->finder = $finder;
        $this->classStorage = $classStorage;
        $this->functionStorage = $functionStorage;
        $this->classExtractor = $classExtractor;
        $this->functionExtractor = $functionExtractor;
        $this->nameResolver = $nameResolver;
        $this->typeResolver = $typeResolver;
        $this->parser = $parser;
        $this->nodeTraverser = $nodeTraverser;
        $this->logger = $logger;

        // Important: NameResolver before other resolvers
        $this->nodeTraverser->addVisitor($this->nameResolver);
        $this->nodeTraverser->addVisitor($this->classExtractor);
        $this->nodeTraverser->addVisitor($this->functionExtractor);
    }

    public function run() {
        $classList = [];
        $functionList = [];

        foreach ($this->finder as $file) {
            $pathSegments = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());

            if (in_array("test", $pathSegments) || in_array("tests", $pathSegments)) {
                $this->logger->debug("Skipping '{file}', because it is a test file.", ["file" => $file->getRelativePathname()]);
                continue;
            }

            $this->classExtractor->setFile($file->getRelativePathname());
            $this->functionExtractor->setFile($file->getRelativePathname());

            try {
                /** @var SplFileInfo $file */
                $nodes = $this->parser->parse($file->getContents());
                $this->nodeTraverser->traverse($nodes);
            } catch (ParseError $e) {
                $file = $file->getRelativePathname();
                $this->logger->warning("Couldn't parse '{$file}': " . $e->getMessage());
                continue;
            }

            $warnings = $this->classExtractor->getWarnings();

            foreach ($this->classExtractor->getClasses() as $class) {
                if (!preg_match("~^vendor/~", $file->getRelativePath())) {
                    $classList[] = "\\" . ltrim($class->namespace, "\\") . $class->name;

                    if (isset($warnings[$class->namespace . $class->name])) {
                        foreach ($warnings[$class->namespace . $class->name] as $member => $warningList) {
                            foreach ($warningList as $warning) {
                                $this->logger->info("{$warning} in '{fqn}'.", ["fqn" => "{$class->namespace}{$class->name}::{$member}"]);
                            }
                        }
                    }
                }

                $this->classStorage->save($class);
            }

            $warnings = $this->functionExtractor->getWarnings();

            foreach ($this->functionExtractor->getFunctions() as $function) {
                // Don't collect information from vendor functions
                if (preg_match("~^vendor/~", $file->getRelativePath())) {
                    continue;
                }

                $fqn = $function->namespace . $function->name;
                $functionList[] = "\\" . ltrim($function->namespace, "\\") . $function->name;

                $this->functionStorage->save($function);

                if (isset($warnings[$fqn])) {
                    foreach ($warnings[$fqn] as $warning) {
                        $this->logger->info("{$warning} in '{fqn}'.", ["fqn" => $function->namespace . $function->name]);
                    }
                }
            }
        }

        return new RunResult($classList, $functionList);
    }
}