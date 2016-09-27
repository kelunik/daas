<?php

namespace Kelunik\DaaS\Persistence;

use function Amp\resolve;
use Kelunik\DaaS\Extraction\Docs\FunctionDoc;
use function Kelunik\DaaS\getName;
use function Kelunik\DaaS\getNamespace;
use Kelunik\DaaS\Package;

class FunctionRepository {
    private $elasticClient;

    public function __construct(ElasticClient $elasticClient) {
        $this->elasticClient = $elasticClient;
    }

    public function put(Package $package, string $version, FunctionDoc $functionDoc) {
        $id = $this->getId($package, $version, $functionDoc->namespace . $functionDoc->name);

        $function = json_decode(json_encode($functionDoc), true);

        $function["parameters"] = array_values($function["parameters"]);

        return $this->elasticClient->put("reference", "function", $id, json_encode($function));
    }

    public function get(Package $package, string $version, string $fqn) {
        $id = $this->getId($package, $version, $fqn);

        return $this->elasticClient->get("reference", "function", $id);
    }

    public function getList(string $vendor, string $packageName, string $version, array $options = []) {
        return resolve(function () use ($vendor, $packageName, $version, $options) {
            $result = yield $this->elasticClient->search("reference", "function", [
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
            $result = yield $this->elasticClient->search("reference", "function", [
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