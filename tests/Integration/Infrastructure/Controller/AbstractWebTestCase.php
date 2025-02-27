<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Infrastructure\Security\Provider\LocalPasswordUserProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected function login(string $email = UserFixture::MAIN_ORG_USER_EMAIL): KernelBrowser
    {
        $client = static::createClient();

        /** @var LocalPasswordUserProvider */
        $userProvider = static::getContainer()->get(LocalPasswordUserProvider::class);
        $user = $userProvider->loadUserByIdentifier($email);

        $client->loginUser($user);

        return $client;
    }

    protected function assertMetaTitle(string $title, Crawler $crawler): void
    {
        $this->assertEquals($title, $crawler->filter('title')->text());
    }

    protected function assertSecurityHeaders(): void
    {
        $this->assertResponseHeaderSame('X-XSS-Protection', '1; mode=block');
        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertResponseHasHeader('X-Content-Security-Policy');
        $this->assertResponseHasHeader('Content-Security-Policy');
    }

    protected function assertNavStructure(array $expectedStructure, Crawler $crawler): void
    {
        $actualStructure = $crawler
            ->filter('header nav a')
            ->each(function (Crawler $node, int $i): array {
                return [$node->text(), ['href' => $node->attr('href'), 'aria-current' => $node->attr('aria-current')]];
            });

        $this->assertEquals($expectedStructure, $actualStructure);
    }

    /**
     * Return a list of node name, text and attributes for headings, links and buttons.
     */
    protected function assertPageStructure(array $expectedStructure, Crawler $crawler): void
    {
        $actualStructure = $crawler
            ->filter('h1, h2, h3, h4, h5, h6, main > :not(noscript) a, main button')
            ->each(function (Crawler $node, int $i): array {
                switch ($node->nodeName()) {
                    case 'a':
                        return ['a', $node->text(), ['href' => $node->attr('href')]];
                    default:
                        return [$node->nodeName(), $node->text()];
                }
            });

        $this->assertEquals($expectedStructure, $actualStructure);
    }

    protected function assertSkipLinks(array $expectedSkipLinks, Crawler $crawler): void
    {
        $actualSkipLinks = $crawler
            ->filter('nav[role=navigation][aria-label="Accès rapide"]')
            ->filter('ul > li > a')
            ->each(fn (Crawler $node, int $i): array => [$node->text(), $node->attr('href')]);

        $this->assertEquals($expectedSkipLinks, $actualSkipLinks);
    }

    protected function getFlashes(Crawler $crawler): array
    {
        $flashes = [];

        foreach (['success', 'error'] as $type) {
            $flashesOfType = array_map(
                fn ($text) => trim($text),
                $crawler->filter(\sprintf('[data-test-flash-type="%s"]', $type))->extract(['_text']),
            );

            if (\count($flashesOfType) > 0) {
                $flashes[$type] = $flashesOfType;
            }
        }

        return $flashes;
    }
}
