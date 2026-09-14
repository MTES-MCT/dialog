<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\TchapInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publie un message dans un salon Tchap (Matrix) via l'API client.
 *
 * L'authentification utilise le jeton d'accès d'un compte de service qui doit
 * avoir rejoint le salon, lequel doit être non chiffré.
 */
final readonly class Tchap implements TchapInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $tchapHomeserverUrl,
        private string $tchapAccessToken,
        private string $tchapRoomId,
    ) {
    }

    public function post(string $body, string $formattedBody): void
    {
        $url = \sprintf(
            '%s/_matrix/client/v3/rooms/%s/send/m.room.message/%s',
            rtrim($this->tchapHomeserverUrl, '/'),
            rawurlencode($this->tchapRoomId),
            Uuid::v4()->toRfc4122(),
        );

        $this->httpClient->request('PUT', $url, [
            'auth_bearer' => $this->tchapAccessToken,
            'json' => [
                'msgtype' => 'm.text',
                'body' => $body,
                'format' => 'org.matrix.custom.html',
                'formatted_body' => $formattedBody,
            ],
        ]);
    }
}
