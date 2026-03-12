<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Litteralis;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Crée un LitteralisClient configuré pour la couche WFS Communication (LIcommunication). */
final class LitteralisCommunicationClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $litteralisWfsHttpClient,
    ) {
    }

    public function create(string $credentials): LitteralisClient
    {
        $client = new LitteralisClient($this->litteralisWfsHttpClient, LitteralisClient::TYPENAME_COMMUNICATION);
        $client->setCredentials($credentials);

        return $client;
    }
}
