<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Domain\User\Organization;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Infrastructure\Persistence\Doctrine\Repository\User\UserRepository;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected function login(string $email = UserFixture::MAIN_ORG_USER_EMAIL): KernelBrowser
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $adminEmail = static::getContainer()->getParameter('admin_email');
        $testUser = $userRepository->findOneByEmail($email);
        $role = $testUser->getEmail() === $adminEmail ? 'ROLE_ADMIN' : 'ROLE_USER';
        $organizations = [];

        foreach ($testUser->getOrganizations() as $organization) {
            $organizations[] = $em->getReference(Organization::class, $organization->getUuid());
        }

        $client->loginUser(
            new SymfonyUser(
                $testUser->getUuid(),
                $testUser->getEmail(),
                $testUser->getFullName(),
                $testUser->getPassword(),
                $organizations,
                [$role],
            ),
        );

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

        $this->assertSame(\count($expectedStructure), \count($actualStructure));

        foreach ($expectedStructure as $index => [$text, $attrs]) {
            [$actualText, $actualAttrs] = $actualStructure[$index];
            $this->assertSame($text, $actualText);
            $this->assertEmpty(array_diff($attrs, $actualAttrs));
        }
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
}
