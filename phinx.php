<?php

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required("APP_DATABASE");

$databaseUri = new Amp\Artax\Uri(getenv("APP_DATABASE"));

$host = $databaseUri->getHost() ?: "localhost";
$port = $databaseUri->getPort() ?: 3306;
$user = $databaseUri->getUser();
$pass = $databaseUri->getPass();
$name = ltrim($databaseUri->getPath(), "/");

if ($databaseUri->getScheme() !== "mysql" || !$user || !$pass || !$name) {
    throw new Exception("Invalid database configuration");
}

$pdo = new PDO(sprintf("mysql:host=%s:%s;dbname=%s;charset=utf8mb4", $host, $port, $name), $user, $pass, [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

return [
    "paths" => [
        "migrations" => "%%PHINX_CONFIG_DIR%%/res/database/migrations",
        "seeds" => "%%PHINX_CONFIG_DIR%%/res/database/seeds",
    ],
    "environments" => [
        "default_database" => "default",
        "default" => [
            "name" => $name,
            "connection" => $pdo,
        ],
    ],
];
