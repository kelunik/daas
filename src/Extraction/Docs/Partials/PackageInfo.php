<?php

namespace Kelunik\DaaS\Extraction\Docs\Partials;

use function Kelunik\DaaS\normalizeVersionString;

trait PackageInfo {
    public $packageVendor;
    public $packageName;
    public $packageVersion;

    public function setPackageVendor(string $packageVendor) {
        $this->packageVendor = $packageVendor;
    }

    public function setPackageName(string $packageName) {
        $this->packageName = $packageName;
    }

    public function setPackageVersion(string $packageVersion) {
        $this->packageVersion = normalizeVersionString($packageVersion);
    }
}