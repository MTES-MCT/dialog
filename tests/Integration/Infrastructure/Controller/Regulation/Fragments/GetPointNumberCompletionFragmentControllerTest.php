<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetPointNumberCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testCompletionAll(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/point-number-completions?search=&administrator=DIR Ouest&roadNumber=N12');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('329 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(329, $options->count());

        $this->assertSame('0 (22)', $options->first()->text());
        $this->assertSame('22##0', $options->first()->attr('data-autocomplete-value'));

        // Les options doivent être triées par PR croissant puis par département croissant
        $pointNumbers = $options->each(fn ($node) => $node->attr('data-autocomplete-value'));
        $actualHash = hash('md5', json_encode($pointNumbers));

        usort($pointNumbers, function ($a, $b) {
            [$dptA, $prA] = explode('##', $a);
            [$dptB, $prB] = explode('##', $b);

            if ($prA == $prB) {
                if ($dptA == $dptB) {
                    return 0;
                }

                return ((int) $dptA > (int) $dptB) ? 1 : -1;
            }

            return (int) $prA > (int) $prB ? 1 : -1;
        });

        $sortedHash = hash('md5', json_encode($pointNumbers));
        $this->assertSame($sortedHash, $actualHash);
    }

    public function testCompletionSearch(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/point-number-completions?search=12&administrator=DIR Ouest&roadNumber=N12');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('8 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(8, $options->count());

        $options->each(function ($node) {
            $this->assertStringStartsWith('12', $node->text());
        });
    }

    public function testCompletionNoResults(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/point-number-completions?search=BlahBlah&administrator=DIR Ouest&roadNumber=N12');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Aucun résultat', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(0, $options->count());
    }

    private function provideNotFound(): array
    {
        return [
            'roadNumber-missing' => ['search=&administrator=DIR Ouest'],
            'administrator-missing' => ['search=&roadNumber=N12'],
        ];
    }

    /**
     * @dataProvider provideNotFound
     */
    public function testNotFound(string $query): void
    {
        $client = $this->login();
        $client->request('GET', \sprintf('/_fragment/point-number-completions?%s', $query));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/point-number-completions?search=Test&administrator=DIR Ouest&roadNumber=N12');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
