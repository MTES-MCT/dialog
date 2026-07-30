<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetHouseNumberOptionsFragmentControllerTest extends AbstractWebTestCase
{
    public function testHouseNumbersAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/house-number-options?roadBanId=75104_0092&targetIds=["from-options", "to-options"]');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $fromOptions = $crawler->filter('turbo-stream[action="update"][target="from-options"] option');
        $toOptions = $crawler->filter('[action="update"][target="to-options"] option');
        foreach ([$fromOptions, $toOptions] as $options) {
            $this->assertCount(4, $options);
            $this->assertSame('1', $options->eq(0)->attr('value'));
            $this->assertSame('2', $options->eq(1)->attr('value'));
            $this->assertSame('6bis', $options->eq(2)->attr('value'));
            $this->assertSame('10', $options->eq(3)->attr('value'));
        }
    }

    private function provideTestParamMissing(): array
    {
        return [
            'missing-roadBanId-targetIds' => ['/_fragment/house-number-options'],
            'missing-roadBanId' => ['/_fragment/house-number-options?targetIds=["from-options"]'],
            'missing-targetIds' => ['/_fragment/house-number-options?roadBanId=75104_0092'],
        ];
    }

    /**
     * @dataProvider provideTestParamMissing
     */
    public function testParamMissing(string $url): void
    {
        $client = $this->login();
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/house-number-options?roadBanId=75104_0092&targetIds=["from-options"]');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
