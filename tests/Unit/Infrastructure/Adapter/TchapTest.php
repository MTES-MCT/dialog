<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\Tchap;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TchapTest extends TestCase
{
    public function testPost(): void
    {
        $homeserverUrl = 'https://matrix.agent.tchap.gouv.fr';
        $accessToken = 'syt_service_account_token';
        $roomId = '!abcdef:agent.tchap.gouv.fr';

        $httpClient = $this->createMock(HttpClientInterface::class);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'PUT',
                self::callback(function (string $url) use ($homeserverUrl): bool {
                    return str_starts_with(
                        $url,
                        $homeserverUrl . '/_matrix/client/v3/rooms/%21abcdef%3Aagent.tchap.gouv.fr/send/m.room.message/',
                    );
                }),
                self::callback(function (array $options) use ($accessToken): bool {
                    return $options['auth_bearer'] === $accessToken
                        && $options['json'] === [
                            'msgtype' => 'm.text',
                            'body' => 'Hello Tchap',
                            'format' => 'org.matrix.custom.html',
                            'formatted_body' => '<p>Hello Tchap</p>',
                        ];
                }),
            );

        $tchap = new Tchap($httpClient, $homeserverUrl, $accessToken, $roomId);
        $tchap->post('Hello Tchap', '<p>Hello Tchap</p>');
    }

    public function testPostTrimsTrailingSlashFromHomeserverUrl(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'PUT',
                self::stringStartsWith('https://matrix.example.com/_matrix/client/v3/rooms/'),
                self::anything(),
            );

        $tchap = new Tchap($httpClient, 'https://matrix.example.com/', 'token', '!room:example.com');
        $tchap->post('body', '<p>body</p>');
    }
}
