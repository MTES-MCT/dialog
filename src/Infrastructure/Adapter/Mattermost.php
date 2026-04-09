<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\MattermostInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Mattermost implements MattermostInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $mattermostWebhookUrl,
    ) {
    }

    public function post(string $text): void
    {
        $this->httpClient->request('POST', $this->mattermostWebhookUrl, [
            'json' => [
                'text' => $text,
            ],
        ]);
    }
}
