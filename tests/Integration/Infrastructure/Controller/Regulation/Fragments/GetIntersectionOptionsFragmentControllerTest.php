<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetIntersectionOptionsFragmentControllerTest extends AbstractWebTestCase
{
    public function testIntersectionsAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/intersection-options?roadBanId=75104_0092&cityCode=75104&targetIds=["from", "to"]');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $fromOptions = $crawler->filter('turbo-stream[action="update"][target="from"] option');
        $toOptions = $crawler->filter('[action="update"][target="to"] option');
        foreach ([$fromOptions, $toOptions] as $options) {
            $this->assertCount(2, $options);
            $this->assertSame('75104_6559', $options->eq(0)->attr('data-value'));
            $this->assertSame('Boulevard Morland', $options->eq(0)->text());
            $this->assertSame('75104_4586', $options->eq(1)->attr('data-value'));
            $this->assertSame('Quai Henri IV', $options->eq(1)->text());
        }
    }

    private function provideTestParamMissing(): array
    {
        return [
            'missing-roadBanId-cityCode-targetIds' => ['/_fragment/intersection-options'],
            'missing-roadBanId' => ['/_fragment/intersection-options?cityCode=75104&targetIds=["t"]'],
            'missing-cityCode' => ['/_fragment/intersection-options?roadBanId=75104_0092&&targetIds=["t"]'],
            'missing-targetIds' => ['/_fragment/intersection-options?roadBanId=75104_0092&cityCode=75104'],
        ];
    }

    /**
     * @dataProvider provideTestBadRequest
     */
    public function testBadRequest(string $url): void
    {
        $client = $this->login();
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/intersection-options?roadBanId=75104_0092&cityCode=75104&targetIds=["t"]');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
