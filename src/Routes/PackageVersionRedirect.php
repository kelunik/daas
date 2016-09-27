<?php

namespace Kelunik\DaaS\Routes;

use Aerys\Request;
use Aerys\Response;
use Kelunik\DaaS\Persistence\PackageRepository;
use Kelunik\DaaS\Rendering\Twig;
use function Kelunik\DaaS\buildUri;

class PackageVersionRedirect {
    private $twig;
    private $packageRepository;

    public function __construct(Twig $twig, PackageRepository $packageRepository) {
        $this->twig = $twig;
        $this->packageRepository = $packageRepository;
    }

    public function __invoke(Request $request, Response $response, array $args) {
        $versions = yield $this->packageRepository->getPackageVersions($args["vendor"], $args["package"], 1);

        if (count($versions) === 0) {
            $response->setStatus(404);
            $response->end();

            return;
        }

        $response->setStatus(302);
        $response->setHeader("location", buildUri("/%s/%s/%s", $args["vendor"], $args["package"], $versions[0]));
        $response->end();
    }
}