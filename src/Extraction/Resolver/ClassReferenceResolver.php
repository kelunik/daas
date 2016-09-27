<?php

namespace Kelunik\DaaS\Extraction\Resolver;

use function Kelunik\DaaS\createClassDocFromReflection;
use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use Kelunik\DaaS\Extraction\Storage\ClassStorage;
use Kelunik\DaaS\Extraction\Storage\StorageException;
use Psr\Log\LoggerInterface as PsrLogger;
use ReflectionClass;

abstract class ClassReferenceResolver {
    private $logger;
    private $classStorage;
    private $docProperty;

    protected function __construct(PsrLogger $logger, ClassStorage $classStorage, string $docProperty) {
        $this->classStorage = $classStorage;
        $this->docProperty = $docProperty;
        $this->logger = $logger;
    }

    protected function resolveReference(ClassDoc $classDoc, ClassDoc $parent) {
        $classDoc->mergeProperties($parent);
        $classDoc->mergeMethods($parent);
    }

    public function resolve(string $fqn) {
        $classDoc = $this->classStorage->load($fqn);
        $property = $this->docProperty;

        $finishedList = [$fqn => $classDoc];
        $pendingList = $classDoc->$property;

        while ($pendingList) {
            $parent = array_shift($pendingList);

            if (isset($finishedList[$parent])) {
                // Cycle detected, skip complete class
                $this->logger->warning("Cycle detected: " . implode(" → ", array_keys($finishedList)));
                return;
            }

            try {
                $parentClassDoc = $this->classStorage->load($parent);
            } catch (StorageException $e) {
                if (class_exists($parent, false) && ($reflectionClass = new ReflectionClass($parent)) && $reflectionClass->isInternal()) {
                    $parentClassDoc = createClassDocFromReflection($reflectionClass);
                } else {
                    $this->logger->warning("Class not found: " . implode(" → ", array_merge(array_keys($finishedList), [$parent])));
                    continue;
                }
            }

            $pendingList = array_merge($pendingList, $parentClassDoc->$property);
            $finishedList[$parent] = $parentClassDoc;

            $this->resolveReference($classDoc, $parentClassDoc);
        }

        $this->classStorage->save($classDoc);
    }
}