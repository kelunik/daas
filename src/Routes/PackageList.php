<?php

namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Twig;
use Kelunik\DaaS\Rendering\TwigContext;

class PackageList {
    private $twig;
    private $packageRepository;

    public function __construct(Twig $twig, PackageRepository $packageRepository) {
        $this->twig = $twig;
        $this->packageRepository = $packageRepository;
    }

    public function __invoke(Request $request, Response $response) {
        $packages = yield $this->packageRepository->getPackages();

        $html = $this->twig->render("package-list.twig", TwigContext::fromRequest($request, [
            "packages" => $packages,
        ]));

        $response->end($html);
    }
}