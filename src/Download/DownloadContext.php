<?php


namespace Kelunik\DaaS\Download;

use Kelunik\DaaS\Package;

class DownloadContext {
    private $destination;
    private $package;
    private $version;
    private $meta;
    private $downloads;

    public function __construct(string $destination, Package $package, string $version, $meta) {
        $this->destination = $destination;
        $this->package = $package;
        $this->version = $version;
        $this->meta = $meta;
        $this->downloads = [];
    }

    public function getDestination(): string {
        return $this->destination;
    }

    public function getPackage(): Package {
        return $this->package;
    }

    public function getVersion(): string {
        return $this->version;
    }

    public function getMeta() {
        return $this->meta;
    }

    public function addDownload(Package $package, string $version, string $source) {
        $this->downloads[$package->getFullName()] = [
            "version" => $version,
            "source" => $source,
        ];
    }

    public function getDownloads(): array {
        return $this->downloads;
    }
}