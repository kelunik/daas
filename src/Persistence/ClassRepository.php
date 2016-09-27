<?php

namespace Kelunik\DaaS\Persistence;

use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use function Kelunik\DaaS\getName;
use function Kelunik\DaaS\getNamespace;
use Kelunik\DaaS\Package;
use function Amp\resolve;

class ClassRepository {
    private $elasticClient;

    public function __construct(ElasticClient $elasticClient) {
        $this->elasticClient = $elasticClient;
    }

    public function put(Package $package, string $version, ClassDoc $classDoc) {
        $id = $this->getId($package, $version, $classDoc->namespace . $classDoc->name);

        $class = json_decode(json_encode($classDoc), true);

        $class["properties"] = array_values($class["properties"]);
        $class["methods"] = array_values($class["methods"]);

        foreach ($class["methods"] as &$method) {
            $method["parameters"] = array_values($method["parameters"]);
        }

        return $this->elasticClient->put("reference", "class", $id, json_encode($class));
    }

    public function get(Package $package, string $version, string $fqn) {
        $id = $this->getId($package, $version, $fqn);

        return $this->elasticClient->get("reference", "class", $id);
    }

    public function getList(string $vendor, string $packageName, string $version, array $options = []) {
        return resolve(function () use ($vendor, $packageName, $version, $options) {
            $result = yield $this->elasticClient->search("reference", "class", [
                "bool" => [
                    "must" => [
                        ["term" => ["packageVendor" => $vendor]],
                        ["term" => ["packageName" => $packageName]],
                        ["term" => ["packageVersion" => $version]],
                    ],
                ],
            ], $options);

            return array_map(function (array $hit) {
                return $hit["_source"];
            }, $result["hits"]["hits"]);
        });
    }

    public function getVersions(Package $package, string $fqn) {
        return resolve(function () use ($package, $fqn) {
            $result = yield $this->elasticClient->search("reference", "class", [
                "bool" => [
                    "must" => [
                        ["term" => ["packageVendor" => $package->getVendor()]],
                        ["term" => ["packageName" => $package->getPackageName()]],
                        ["match" => ["name" => getName($fqn)]],
                        ["match" => ["namespace" => getNamespace($fqn)]],
                    ],
                ],
            ], [
                "include" => ["packageVersion"],
                "limit" => 100,
            ]);

            return array_map(function (array $hit) {
                return $hit["_source"]["packageVersion"];
            }, $result["hits"]["hits"]);
        });
    }

    private function getId(Package $package, string $version, string $fqn): string {
        return strtolower(sprintf(
            "%s/%s/%s/%s",
            $package->getVendor(),
            $package->getPackageName(),
            $version,
            ltrim(str_replace("\\", "/", $fqn), "\\")
        ));
    }
}