<?php

namespace Kelunik\DaaS;

use Amp\Artax\Uri;
use Kelunik\DaaS\Extraction\Docs\ClassDoc;
use Kelunik\DaaS\Extraction\Docs\MethodDoc;
use Kelunik\DaaS\Extraction\Docs\ParameterDoc;
use Kelunik\DaaS\Extraction\Docs\PropertyDoc;
use PhpParser\Node\Stmt\Class_;
use ReflectionClass;

/**
 * Checks whether a string is a valid version number.
 *
 * @param string $version Version string to check.
 * @return bool
 * @throws RegexException
 */
function isValidVersionString(string $version): bool {
    $match = preg_match("~^v?\\d+\\.\\d+\\.\\d+$~", $version);

    if ($match === false) {
        throw new RegexException(preg_last_error());
    }

    return (bool) $match;
}

function isValidPackageName(string $package): bool {
    $match = preg_match("~^[^/]+/[^/]+$~", $package);

    if ($match === false) {
        throw new RegexException(preg_last_error());
    }

    return (bool) $match;
}

function normalizeVersionString(string $version): string {
    $match = preg_match("~^v?(\\d+)\\.(\\d+)\\.(\\d+)$~", $version, $versionInfo);

    if ($match === false) {
        throw new RegexException(preg_last_error());
    }

    if ($match === 0) {
        throw new InvalidArgumentException("Invalid package version: '{$version}'");
    }

    list(, $major, $minor, $patch) = $versionInfo;

    return "v{$major}.{$minor}.{$patch}";
}

function getVersionId(string $version): int {
    $match = preg_match("~^v?(\\d+)\\.(\\d+)\\.(\\d+)$~", $version, $versionInfo);

    if ($match === false) {
        throw new RegexException(preg_last_error());
    }

    if ($match === 0) {
        throw new InvalidArgumentException("Invalid package version: '{$version}'");
    }

    list(, $major, $minor, $patch) = $versionInfo;

    return 1000000 * $major + 1000 * $minor + $patch;
}

function getVersionString(int $versionId): string {
    return sprintf(
        "v%s.%s.%s",
        (int) ($versionId / 1000000),
        (int) ($versionId % 1000000 / 1000),
        (int) ($versionId % 1000)
    );
}

function buildUri(string $uri, ...$params) {
    return sprintf($uri, ...array_map("rawurlencode", $params));
}

function isCommandLineFlag(string $string): bool {
    if (strlen($string) === 0) {
        return false;
    }

    return $string[0] === "-";
}

function getNamespace(string $fqn): string {
    $lastPos = strrpos($fqn, "\\");

    if ($lastPos === false) {
        return "";
    }

    return substr($fqn, 0, $lastPos) . "\\";
}

function getName(string $fqn): string {
    $lastPos = strrpos($fqn, "\\");

    if ($lastPos === false) {
        return $fqn;
    }

    return substr($fqn, $lastPos + 1);
}

function getVisibilityFromType(int $type): string {
    if ($type & Class_::MODIFIER_PUBLIC) {
        return "public";
    }

    if ($type & Class_::MODIFIER_PROTECTED) {
        return "protected";
    }

    if ($type & Class_::MODIFIER_PRIVATE) {
        return "private";
    }

    return "public";
}

function base64url_encode(string $string): string {
    return rtrim(strtr(base64_encode($string), "+/", "-_"), "=");
}

function base64url_decode(string $string): string {
    return base64_encode(strtr($string, "-_", "+/"));
}

/**
 * Checks whether a type is special.
 *
 * A type is special if it's not a class or interface, but a keyword.
 *
 * @param string $type type to check
 * @return bool whether the type is special or not
 * @see https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#keyword
 */
function isSpecialType(string $type) {
    static $specialTypes = [
        "string" => true,
        "int" => true,
        "bool" => true,
        "float" => true,
        "object" => true,
        "mixed" => true,
        "array" => true,
        "resource" => true,
        "void" => true,
        "null" => true,
        "callable" => true,
        "false" => true,
        "true" => true,
        "self" => true,
        "static" => true,
        "\$this" => true,
    ];

    return isset($specialTypes[$type]);
}

/**
 * Improved export of values to PHP code.
 *
 * Uses short array syntax and supports stdClass instances.
 *
 * @param mixed $value Value to export.
 * @return string PHP code representing the value.
 */
function value_export($value) {
    if ($value instanceof \stdClass) {
        return "(object) " . value_export(get_object_vars($value));
    }

    if (is_array($value)) {
        $array = [];

        foreach ($value as $key => $val) {
            $array[] = value_export($key) . " => " . value_export($value);
        }

        return "[" . implode(", ", $array) . "]";
    }

    // PHP usually uses an uppercase string here
    if ($value === null) {
        return "null";
    }

    return var_export($value, true);
}

function createClassDocFromReflection(ReflectionClass $reflectionClass) {
    $classDoc = new ClassDoc($reflectionClass->getShortName());
    $classDoc->setNamespace(getNamespace($reflectionClass->name));
    $classDoc->setClassType($reflectionClass->isInterface() ? "interface" : ($reflectionClass->isTrait() ? "trait" : "class"));

    $defaultProperties = $reflectionClass->getDefaultProperties();

    foreach ($reflectionClass->getProperties() as $reflectionProperty) {
        if ($reflectionProperty->isPrivate()) {
            continue;
        }

        $property = new PropertyDoc($reflectionProperty->name);
        $property->setVisibility($reflectionProperty->isPrivate() ? "private" : ($reflectionProperty->isProtected() ? "protected" : "public"));
        $property->setDefiningClass($reflectionProperty->getDeclaringClass()->getName());
        $property->setStatic($reflectionProperty->isStatic());
        $property->setDefaultValue(value_export($defaultProperties[$reflectionProperty->name]));

        $classDoc->properties[] = $property;
    }

    foreach ($reflectionClass->getMethods() as $reflectionMethod) {
        if ($reflectionMethod->isPrivate()) {
            continue;
        }

        $method = new MethodDoc($reflectionMethod->name);
        $method->setDefiningClass($reflectionMethod->class);
        $method->setAbstract($reflectionMethod->isAbstract());
        $method->setFinal($reflectionMethod->isFinal());
        $method->setStatic($reflectionMethod->isStatic());
        $method->setVisibility($reflectionMethod->isPrivate() ? "private" : ($reflectionMethod->isProtected() ? "protected" : "public"));
        $method->setReturnType((string) $reflectionMethod->getReturnType());
        $method->setReturnByRef($reflectionMethod->returnsReference());

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $param = new ParameterDoc($reflectionParameter->name);
            $param->setByRef($reflectionParameter->isPassedByReference());
            $param->setType((string) $reflectionParameter->getType());
            $param->setVariadic($reflectionParameter->isVariadic());

            if ($reflectionParameter->isDefaultValueAvailable()) {
                if ($reflectionParameter->isDefaultValueConstant()) {
                    $param->setDefaultValue($reflectionParameter->getDefaultValueConstantName());
                } else {
                    $defaultValue = $reflectionParameter->getDefaultValue();
                    $param->setDefaultValue(value_export($defaultValue));
                }
            }

            $method->addParameter($param);
        }

        $classDoc->methods[] = $method;
    }

    return $classDoc;
}

function databaseUriToConnStr($uri) {
    $databaseUri = new Uri($uri);

    $host = $databaseUri->getHost() ?: "localhost";
    $port = $databaseUri->getPort() ?: 3306;
    $user = $databaseUri->getUser();
    $pass = $databaseUri->getPass();
    $name = ltrim($databaseUri->getPath(), "/");

    if ($databaseUri->getScheme() !== "mysql" || !$user || !$pass || !$name) {
        throw new \Exception("Invalid database configuration");
    }

    return "host={$host}:{$port};user={$user};pass={$pass};db={$name}";
}

function rrmdir($src) {
    $dir = opendir($src);

    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $full = $src . '/' . $file;

        if (is_dir($full)) {
            rrmdir($full);
        } else {
            unlink($full);
        }
    }

    closedir($dir);
    rmdir($src);
}