<?php

namespace Kelunik\DaaS\Download;

use Amp\Artax\Client;
use Amp\Artax\Cookie\NullCookieJar;
use Amp\Artax\Response;
use Amp\Process;
use function Amp\resolve;
use Generator;
use function Kelunik\DaaS\base64url_encode;
use function Kelunik\DaaS\buildUri;
use Kelunik\DaaS\Characters;
use function Kelunik\DaaS\isCommandLineFlag;
use function Kelunik\DaaS\isValidPackageName;
use function Kelunik\DaaS\isValidVersionString;
use function Kelunik\DaaS\normalizeVersionString;
use Kelunik\DaaS\Package;
use const Kelunik\DaaS\ROOT;

class Composer {
    private $baseDir;

    public function __construct(string $baseDir) {
        $this->baseDir = $baseDir;
    }

    public function fetch(Package $package, $version) {
        return resolve(function () use ($package, $version) {
            if (!isValidVersionString($version)) {
                throw new DownloadException("Invalid version: {$version}");
            }

            $packageMeta = yield from $this->fetchPackagistMeta($package);

            $destination = $this->baseDir . "/" . $package->getVendor() . "@" . $package->getPackageName() . "@" . $version;

            $context = new DownloadContext($destination, $package, $version, $packageMeta);

            $command = [
                "composer",
                "create-project",
                "--prefer-dist",
                "--ignore-platform-reqs",
                "--no-scripts",
                "--no-plugins",
                "--no-progress",
                "--no-interaction",
                "--no-ansi",
                "--no-dev",
                $package->getFullName(),
                $destination . "/source",
                $version,
            ];

            $process = new Process($command);
            $promise = $process->exec(Process::BUFFER_ALL);
            $promise->watch(function($update) use ($context) {
                static $buffer = "";

                if ($update[0] !== "err") {
                    return;
                }

                $buffer .= $update[1];

                // Wait for newline at the end, otherwise "Installing ..." ends up in an earlier match and doesn't include the cache info
                if (preg_match_all("~- Installing (\\S+) \\((v?\\d+\\.\\d+\\.\\d+)\\)\\s+(.*)\n~", $buffer, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    foreach ($matches as $match) {
                        $package = Package::fromString($match[1][0]);
                        $version = normalizeVersionString($match[2][0]);

                        switch (trim($match[3][0])) {
                            case "Downloading":
                                $context->addDownload($package, $version, false);
                                break;

                            case "Loading from cache":
                                $context->addDownload($package, $version, true);
                                break;

                            default:
                                throw new DownloadException("Unknown cache status: " . $match[3][0]);
                        }
                    }

                    if (isset($match)) {
                        $buffer = substr($buffer, $match[0][1] + strlen($match[0][0]));
                    }
                }
            });

            $result = yield $promise;

            if ($result->exit !== 0) {
                $message = <<<MESSAGE
Composer project creation failed.

Process standard output
-----------------------
{$result->stdout}

Process error output
--------------------
{$result->stderr}

MESSAGE;

                throw new DownloadException($message);
            }

            return $context;
        });
    }

    private function fetchPackagistMeta(Package $package): Generator {
        $url = buildUri("https://packagist.org/packages/%s/%s.json", $package->getVendor(), $package->getPackageName());

        /** @var Response $packagistResponse */
        $packagistResponse = yield (new Client(new NullCookieJar))->request($url);

        if ($packagistResponse->getStatus() !== 200) {
            throw new DownloadException("Failure to fetch package info");
        }

        $packageMeta = json_decode($packagistResponse->getBody(), true);

        if ($packageMeta === null) {
            throw new DownloadException("Invalid package meta");
        }

        return $packageMeta;
    }
}