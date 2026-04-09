<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\Mattermost;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MattermostTest extends TestCase
{
    public function testPost(): void
    {
        $webhookUrl = 'https://mattermost.example.com/hooks/xxx';
        $httpClient = $this->createMock(HttpClientInterface::class);

        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', $webhookUrl, [
                'json' => [
                    'text' => 'Hello Mattermost',
                ],
            ]);

        $mattermost = new Mattermost($httpClient, $webhookUrl);
        $mattermost->post('Hello Mattermost');
    }
}
