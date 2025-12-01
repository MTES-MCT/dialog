<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\UX\Turbo\TurboBundle;

final class GetSideOptionsFragmentControllerTest extends AbstractWebTestCase
{
    private function provideGet(): array
    {
        return [
            'U' => [
                'query' => [
                    'administrator' => 'DIR Centre Est',
                    'roadNumber' => 'N79',
                    'pointNumberWithDepartmentCode' => '71##21',
                    'currentOption' => '',
                ],
                'sides' => [['U', false]],
            ],
            'DG' => [
                'query' => [
                    'administrator' => 'DIR Ouest',
                    'roadNumber' => 'N12',
                    'pointNumberWithDepartmentCode' => '22##122',
                    'currentOption' => 'G',
                ],
                'sides' => [['D', false], ['G', true]],
            ],
        ];
    }

    /**
     * @dataProvider provideGet
     */
    public function testGet(array $query, array $sides): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', \sprintf('/_fragment/side-options?%s&targetId=sideSelect', http_build_query($query)));

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);
        $this->assertSecurityHeaders();

        $streams = $crawler->filter('turbo-stream');
        $this->assertCount(1, $streams);
        $this->assertSame($streams->eq(0)->attr('action'), 'update');
        $this->assertSame($streams->eq(0)->attr('target'), 'sideSelect');

        $options = $streams->eq(0)->filter('option');
        $this->assertCount(\count($sides), $options);

        foreach ($sides as $index => [$side, $selected]) {
            $this->assertSame($side, $options->eq($index)->attr('value'));
            // Symfony 7.4+ : attr() retourne '' pour les attributs booléens présents sans valeur
            $this->assertSame($selected ? '' : null, $options->eq($index)->attr('selected'));
        }
    }

    public function testNoResults(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', \sprintf('/_fragment/side-options?%s&currentOption=&targetId=sideSelect', http_build_query([
            'administrator' => 'Blahblah',
            'roadNumber' => 'N12',
            'pointNumberWithDepartmentCode' => '22##122',
        ])));

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);
        $this->assertSecurityHeaders();

        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(0, $options->count());
    }

    private function provideNotFound(): array
    {
        return [
            'administrator-missing' => [
                'roadNumber' => 'N12',
                'pointNumberWithDepartmentCode' => '22##122',
            ],
            'roadNumber-missing' => [
                'administrator' => 'DIR Ouest',
                'pointNumberWithDepartmentCode' => '22##122',
            ],
            'pointNumber-missing' => [
                'administrator' => 'DIR Ouest',
                'roadNumber' => 'N12',
            ],
        ];
    }

    /**
     * @dataProvider provideNotFound
     */
    public function testNotFound(string ...$query): void
    {
        $client = $this->login();
        $client->request('GET', \sprintf('/_fragment/side-options?%s&currentOption=&targetId=sideSelect', http_build_query($query)));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();

        $client->request('GET', \sprintf('/_fragment/side-options?%s', http_build_query([
            'administrator' => 'Blahblah',
            'roadNumber' => 'N12',
            'pointNumberWithDepartmentCode' => '22##122',
            'currentOption' => '',
            'targetId' => 'sideSelect',
        ])));

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
