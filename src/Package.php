<?php

namespace Kelunik\DaaS;

class Package {
    const REGEX_NAME_SEGMENT = "[a-z0-9]([_.-]?[a-z0-9]+)*";
    const REGEX_NAME = self::REGEX_NAME_SEGMENT . "/" . self::REGEX_NAME_SEGMENT;

    private $vendor;
    private $packageName;

    public function __construct(string $vendor, string $packageName) {
        if (!self::isValid("{$vendor}/{$packageName}")) {
            throw new InvalidPackageNameException("{$vendor}/{$packageName}");
        }

        $this->vendor = $vendor;
        $this->packageName = $packageName;
    }

    public function getVendor(): string {
        return $this->vendor;
    }

    public function getPackageName(): string {
        return $this->packageName;
    }

    public function getFullName() {
        return $this->vendor . "/" . $this->packageName;
    }

    public static function fromString(string $name) {
        if (!self::isValid($name)) {
            throw new InvalidPackageNameException($name);
        }

        list($vendor, $packageName) = explode("/", $name);

        return new Package($vendor, $packageName);
    }

    public static function isValid(string $name): bool {
        if (!preg_match("~" . self::REGEX_NAME . "~i", $name)) {
            return false;
        }

        return true;
    }
}