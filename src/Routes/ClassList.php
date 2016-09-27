<?php


namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use function Kelunik\DaaS\buildUri;
use Kelunik\DaaS\Persistence\ClassRepository;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Twig;
use Kelunik\DaaS\Rendering\TwigContext;
use function Kelunik\DaaS\isValidVersionString;

class ClassList {
    private $twig;
    private $packageRepository;
    private $classRepository;

    public function __construct(Twig $twig, PackageRepository $packageRepository, ClassRepository $classRepository) {
        $this->twig = $twig;
        $this->packageRepository = $packageRepository;
        $this->classRepository = $classRepository;
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
                "href" => buildUri("/%s/%s/%s/classes", $args["vendor"], $args["package"], $version),
                "text" => $version,
            ];
        }, $versions);

        $classes = yield $this->classRepository->getList($args["vendor"], $args["package"], $args["version"], [
            "limit" => 200,
            "include" => [
                "namespace",
                "name",
            ],
        ]);

        $html = $this->twig->render("class-list.twig", TwigContext::fromRequest($request, [
            "vendor" => $args["vendor"],
            "package" => $args["package"],
            "version" => $args["version"],
            "classes" => $classes,
            "versions" => $versions,
        ]));

        $response->end($html);
    }
}