<?php

namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Twig;
use Kelunik\DaaS\Rendering\TwigContext;
use function Kelunik\DaaS\buildUri;
use function Kelunik\DaaS\isValidVersionString;

class PackageReference {
    private $twig;
    private $packageRepository;

    public function __construct(Twig $twig, PackageRepository $packageRepository) {
        $this->twig = $twig;
        $this->packageRepository = $packageRepository;
    }

    public function __invoke(Request $request, Response $response, array $args) {
        if (!isValidVersionString($args["version"])) {
            $response->setStatus(404);
            $response->end();

            return;
        }

        $versions = yield $this->packageRepository->getPackageVersions($args["vendor"], $args["package"]);
        $versions = array_map(function ($version) use ($args) {
            return [
                "href" => buildUri("/%s/%s/%s", $args["vendor"], $args["package"], $version),
                "text" => $version,
            ];
        }, $versions);

        $html = $this->twig->render("package-reference.twig", TwigContext::fromRequest($request, [
            "vendor" => $args["vendor"],
            "package" => $args["package"],
            "version" => $args["version"],
            "versions" => $versions,
        ]));

        $response->end($html);
    }
}