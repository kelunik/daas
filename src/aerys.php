<?php

use Aerys\Host;
use Amp\Artax\Client;
use Amp\Artax\Cookie\NullCookieJar;
use Amp\Artax\HttpClient;
use Amp\Mysql\Pool;
use Auryn\Injector;
use Dotenv\Dotenv;
use Kelunik\DaaS\Persistence\ElasticClient;
use Kelunik\DaaS\Rendering\LinkFilter;
use Kelunik\DaaS\Rendering\MarkdownFilter;
use Kelunik\DaaS\Rendering\SourceLinkFilter;
use Kelunik\DaaS\Routes\ClassList;
use Kelunik\DaaS\Routes\ClassReference;
use Kelunik\DaaS\Routes\FunctionList;
use Kelunik\DaaS\Routes\FunctionReference;
use Kelunik\DaaS\Routes\PackageList;
use Kelunik\DaaS\Routes\PackageReference;
use Kelunik\DaaS\Routes\PackageVersionRedirect;
use Psr\Log\LoggerInterface;
use const Kelunik\DaaS\ROOT;
use function Aerys\router;
use function Kelunik\DaaS\databaseUriToConnStr;

$dotenv = new Dotenv(__DIR__ . "/..");
$dotenv->load();
$dotenv->required("APP_DATABASE");
$dotenv->required("APP_ELASTICSEARCH");

$injector = new Injector;

$injector->alias(Twig_LoaderInterface::class, Twig_Loader_Filesystem::class);
$injector->alias(LoggerInterface::class, get_class($logger));
$injector->alias(HttpClient::class, Client::class);

$injector->share(Pool::class);
$injector->share(Twig_Environment::class);
$injector->share(new Client(new NullCookieJar()));
$injector->share($logger);

$injector->define(Pool::class, [
    ":connStr" => databaseUriToConnStr(getenv("APP_DATABASE")),
]);

$injector->define(ElasticClient::class, [
    ":baseUri" => getenv("APP_ELASTICSEARCH"),
]);

$injector->define(Twig_Loader_Filesystem::class, [
    ":paths" => ROOT . "/res/templates",
]);

$injector->define(Twig_Environment::class, [
    ":options" => [
        "auto_reload" => $console->isArgDefined("debug"),
        "strict_variables" => true,
    ],
]);

$injector->prepare(Twig_Environment::class, static function (Twig_Environment $twig) use ($injector) {
    $twig->addFilter($injector->make(SourceLinkFilter::class));
    $twig->addFilter($injector->make(MarkdownFilter::class));
    $twig->addFilter($injector->make(LinkFilter::class));
});

$router = router()
    ->route("GET", "/", $injector->make(PackageList::class))
    ->route("GET", "/{vendor}/{package}/?", $injector->make(PackageVersionRedirect::class))
    ->route("GET", "/{vendor}/{package}/{version}/?", $injector->make(PackageReference::class))
    ->route("GET", "/{vendor}/{package}/{version}/classes/?", $injector->make(ClassList::class))
    ->route("GET", "/{vendor}/{package}/{version}/classes/{fqn:.+}", $injector->make(ClassReference::class))
    ->route("GET", "/{vendor}/{package}/{version}/functions/?", $injector->make(FunctionList::class))
    ->route("GET", "/{vendor}/{package}/{version}/functions/{fqn:.+}", $injector->make(FunctionReference::class));

(new Host)
    ->expose("*", 4000)
    ->use(\Aerys\root(ROOT . "/public"))
    ->use($router);