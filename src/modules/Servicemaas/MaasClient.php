<?php
declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Servicemaas;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class MaasClient
{
    private Client $client;
    private string $maasUrl;

    public function __construct(string $maasUrl, string $apiKey)
    {
        $this->maasUrl = rtrim($maasUrl, '/');

        [$consumerKey, $tokenKey, $tokenSecret] = $this->parseApiKey($apiKey);

        $stack = HandlerStack::create();

        $middleware = new Oauth1([
            'consumer_key'    => $consumerKey,
            'token'           => $tokenKey,
            'token_secret'    => $tokenSecret,
            'signature_method' => Oauth1::SIGNATURE_METHOD_HMAC
        ]);
        $stack->push($middleware);

        $this->client = new Client([
            'base_uri' => $this->maasUrl,
            'handler' => $stack,
            'auth' => 'oauth'
        ]);
    }

    private function parseApiKey(string $apiKey): array
    {
        $parts = explode(':', $apiKey);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid MAAS API key format. Expected format: consumer_key:token_key:token_secret');
        }
        return $parts;
    }

    public function get(string $endpoint, array $options = [])
    {
        return $this->request('GET', $endpoint, $options);
    }

    public function post(string $endpoint, array $options = [])
    {
        return $this->request('POST', $endpoint, $options);
    }

    public function put(string $endpoint, array $options = [])
    {
        return $this->request('PUT', $endpoint, $options);
    }

    public function delete(string $endpoint, array $options = [])
    {
        return $this->request('DELETE', $endpoint, $options);
    }

    private function request(string $method, string $endpoint, array $options = [])
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            // In a real application, you would want to log this error.
            throw new \Exception('MAAS API request failed: ' . $e->getMessage());
        }
    }
}
