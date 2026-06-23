<?php

namespace Orlinkzz\Waha\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Orlinkzz\Waha\Exceptions\WahaException;
use Orlinkzz\Waha\WahaConfig;

class WahaHttpClient
{
    private Client $http;

    public function __construct(private readonly WahaConfig $config)
    {
        $this->http = new Client([
            'base_uri' => rtrim($config->baseUrl, '/') . '/',
            'timeout'  => $config->timeout,
            'headers'  => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $config->apiKey,
            ],
        ]);
    }

    public function post(string $endpoint, array $body = []): array
    {
        try {
            $response = $this->http->post($endpoint, ['json' => $body]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new WahaException("WAHA API error on POST {$endpoint}: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->http->get($endpoint, ['query' => $query]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new WahaException("WAHA API error on GET {$endpoint}: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
