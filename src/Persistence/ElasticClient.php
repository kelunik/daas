<?php

namespace Kelunik\DaaS\Persistence;

use Amp\Artax\HttpClient;
use Amp\Artax\Request;
use Amp\Artax\Response;

class ElasticClient {
    private $httpClient;
    private $baseUri;

    public function __construct(HttpClient $httpClient, string $baseUri) {
        $this->httpClient = $httpClient;
        $this->baseUri = $baseUri;
    }

    public function put(string $index, string $type, string $id, string $source) {
        $request = (new Request)
            ->setMethod("PUT")
            ->setUri($this->baseUri . "/" . urlencode($index) . "/" . urlencode($type) . "/" . urlencode($id))
            ->setBody($source);

        return \Amp\pipe($this->httpClient->request($request), function (Response $response) {
            $statusClass = (int) ($response->getStatus() / 100);

            if ($statusClass !== 2) {#
                throw new \Exception("Invalid response code: " . $response->getStatus() . PHP_EOL . PHP_EOL . json_encode(json_decode($response->getBody()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $data = json_decode($response->getBody(), true);

            return $data["created"];
        });
    }

    public function get(string $index, string $type, string $id, array $options = []) {
        $query = [];

        if (isset($options["include"])) {
            $query["_source_include"] = implode(",", $options["include"]);
        }

        if (isset($options["exclude"])) {
            $query["_source_exclude"] = implode(",", $options["exclude"]);
        }

        $request = (new Request)
            ->setMethod("GET")
            ->setUri($this->baseUri . "/" . urlencode($index) . "/" . urlencode($type) . "/" . urlencode($id) . "?" . http_build_query($query));

        return \Amp\pipe($this->httpClient->request($request), function (Response $response) {
            $statusClass = (int) ($response->getStatus() / 100);

            if ($statusClass !== 2) {
                if ($response->getStatus() === 404) {
                    return null;
                }

                throw new \Exception("Invalid response code: " . $response->getStatus());
            }

            $data = json_decode($response->getBody(), true);

            return $data["_source"];
        });
    }

    public function search(string $index, string $type, array $search, array $options = []) {
        $query = [];

        if (isset($options["include"])) {
            $query["_source_include"] = implode(",", $options["include"]);
        }

        if (isset($options["exclude"])) {
            $query["_source_exclude"] = implode(",", $options["exclude"]);
        }

        if (isset($options["limit"])) {
            $query["size"] = $options["limit"];
        }

        $request = (new Request)
            ->setMethod("GET")
            ->setUri($this->baseUri . "/" . urlencode($index) . "/" . urlencode($type) . "/_search?" . http_build_query($query))
            ->setBody(json_encode([
                "query" => $search,
            ]));

        return \Amp\pipe($this->httpClient->request($request), function (Response $response) {
            $statusClass = (int) ($response->getStatus() / 100);

            if ($statusClass !== 2) {
                throw new \Exception("Invalid response code: " . $response->getStatus() . PHP_EOL . PHP_EOL . $response->getBody());
            }

            $data = json_decode($response->getBody(), true);

            return $data;
        });
    }
}