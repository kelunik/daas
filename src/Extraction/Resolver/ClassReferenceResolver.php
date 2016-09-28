<?php

namespace Kelunik\DaaS\Extraction\Resolver;

use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use Kelunik\DaaS\Extraction\Storage\ClassStorage;
use Kelunik\DaaS\Extraction\Storage\StorageException;
use Psr\Log\LoggerInterface as PsrLogger;
use ReflectionClass;
use function Kelunik\DaaS\createClassDocFromReflection;

abstract class ClassReferenceResolver {
    private $logger;
    private $classStorage;
    private $docProperties;

    protected function __construct(PsrLogger $logger, ClassStorage $classStorage, array $docProperties) {
        $this->classStorage = $classStorage;
        $this->docProperties = $docProperties;
        $this->logger = $logger;
    }

    protected function resolveReference(ClassDoc $classDoc, ClassDoc $parent) {
        $classDoc->mergeProperties($parent);
        $classDoc->mergeMethods($parent);
    }

    public function resolve(string $fqn) {
        $classDoc = $this->classStorage->load($fqn);
        $properties = $this->docProperties;

        $finishedList = [$fqn => $classDoc];
        $pendingList = array_merge(...array_map(function ($property) use ($classDoc) {
            return $classDoc->$property;
        }, $properties));

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

            $finishedList[$parent] = $parentClassDoc;
            $pendingList = array_merge($pendingList, ...array_map(function ($property) use ($parentClassDoc) {
                return $parentClassDoc->$property;
            }, $properties));

            $this->resolveReference($classDoc, $parentClassDoc);
        }

        $this->classStorage->save($classDoc);
    }
}