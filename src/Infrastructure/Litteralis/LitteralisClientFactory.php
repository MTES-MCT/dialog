<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LitteralisClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $litteralisWfsHttpClient,
    ) {
    }

    public function create(string $credentials): LitteralisClient
    {
        $client = new LitteralisClient($this->litteralisWfsHttpClient);
        $client->setCredentials($credentials);

        return $client;
    }
}
