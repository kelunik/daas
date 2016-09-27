<?php


namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use Kelunik\DaaS\Package;
use Kelunik\DaaS\Persistence\FunctionRepository;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Markdown;
use Kelunik\DaaS\Rendering\Twig;
use Kelunik\DaaS\Rendering\TwigContext;
use function Kelunik\DaaS\buildUri;
use function Kelunik\DaaS\getVersionId;
use function Kelunik\DaaS\isValidVersionString;

class FunctionReference {
    private $twig;
    private $markdown;
    private $functionRepository;
    private $packageRepository;

    public function __construct(Twig $twig, Markdown $markdown, FunctionRepository $functionRepository, PackageRepository $packageRepository) {
        $this->twig = $twig;
        $this->markdown = $markdown;
        $this->functionRepository = $functionRepository;
        $this->packageRepository = $packageRepository;
    }

    public function __invoke(Request $request, Response $response, array $args) {
        if (!isValidVersionString($args["version"])) {
            $response->setStatus(404);
            $response->end();

            return;
        }

        $package = new Package($args["vendor"], $args["package"]);
        $version = $args["version"];

        $fqn = strtr($args["fqn"], "/", "\\");

        $function = yield $this->functionRepository->get($package, $version, $fqn);

        if ($function === null) {
            $response->setStatus(404);
            $response->end();

            return;
        }

        $versionInfo = yield $this->packageRepository->getPackageVersion($package->getVendor(), $package->getPackageName(), $args["version"]);

        if (!$versionInfo) {
            $response->setStatus(404);
            $response->end();

            return;
        }

        $versions = yield $this->functionRepository->getVersions($package, strtr($args["fqn"], "/", "\\"));

        usort($versions, function ($a, $b) {
            return getVersionId($b) <=> getVersionId($a);
        });

        $versions = array_map(function ($version) use ($package, $args) {
            return [
                "href" => buildUri("/%s/%s/%s/functions/" . str_replace("%2F", "/", rawurldecode($args["fqn"])), $package->getVendor(), $package->getPackageName(), $version),
                "text" => $version,
            ];
        }, $versions);

        $html = $this->twig->render("function-reference.twig", TwigContext::fromRequest($request, [
            "package_blob_url" => "{$versionInfo["href"]}/blob/{$versionInfo["tag"]}",
            "vendor" => $args["vendor"],
            "package" => $args["package"],
            "version" => $args["version"],
            "versions" => $versions,
            "function" => $function,
        ]));

        $response->end($html);
    }
}