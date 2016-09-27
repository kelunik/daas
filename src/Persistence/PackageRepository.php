<?php

namespace Kelunik\DaaS\Persistence;

use Amp\Mysql\Pool;
use Amp\Mysql\ResultSet;
use Amp\Promise;
use function Amp\resolve;
use function Kelunik\DaaS\getVersionId;
use function Kelunik\DaaS\getVersionString;

class PackageRepository {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function getPackages(): Promise {
        return resolve(function() {
            /** @var ResultSet $result */
            $result = yield $this->mysql->query("SELECT vendor, name FROM packages ORDER BY vendor ASC, name ASC");

            return yield $result->fetchAll();
        });
    }

    public function getPackageVersions(string $vendor, string $package, int $limit = 100): Promise {
        return resolve(function() use ($vendor, $package, $limit) {
            /** @var ResultSet $result */
            $result = yield $this->mysql->prepare("SELECT version FROM versions WHERE vendor = ? && name = ? ORDER BY version DESC LIMIT ?", [
                $vendor, $package, $limit
            ]);

            return array_map(function($item) {
                return getVersionString($item["version"]);
            }, yield $result->fetchAll());
        });
    }

    public function getPackageVersion(string $vendor, string $package, string $version): Promise {
        return resolve(function() use ($vendor, $package, $version) {
            /** @var ResultSet $result */
            $result = yield $this->mysql->prepare("SELECT p.vendor, p.name, p.platform, p.href, v.version, v.tag FROM packages p JOIN versions v ON (p.vendor = v.vendor && p.name = v.name) WHERE p.vendor = ? && p.name = ? && v.version = ? LIMIT 1", [
                $vendor, $package, getVersionId($version),
            ]);

            $package = yield $result->fetch();

            if ($package) {
                $package["version"] = getVersionString($package["version"]);
            }

            return $package;
        });
    }
}