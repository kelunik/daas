<?php


namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use function Kelunik\DaaS\buildUri;
use function Kelunik\DaaS\isValidVersionString;
use Kelunik\DaaS\Persistence\FunctionRepository;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Twig;
use Kelunik\DaaS\Rendering\TwigContext;

class FunctionList {
    private $twig;
    private $packageRepository;
    private $functionRepository;

    public function __construct(Twig $twig, PackageRepository $packageRepository, FunctionRepository $functionRepository) {
        $this->twig = $twig;
        $this->packageRepository = $packageRepository;
        $this->functionRepository = $functionRepository;
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
                "href" => buildUri("/%s/%s/%s/functions", $args["vendor"], $args["package"], $version),
                "text" => $version,
            ];
        }, $versions);

        $functions = yield $this->functionRepository->getList($args["vendor"], $args["package"], $args["version"], [
            "limit" => 200,
            "include" => [
                "namespace",
                "name",
            ],
        ]);

        $html = $this->twig->render("function-list.twig", TwigContext::fromRequest($request, [
            "vendor" => $args["vendor"],
            "package" => $args["package"],
            "version" => $args["version"],
            "functions" => $functions,
            "versions" => $versions,
        ]));

        $response->end($html);
    }
}