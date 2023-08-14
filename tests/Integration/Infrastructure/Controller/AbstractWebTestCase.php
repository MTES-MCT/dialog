<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Domain\User\Organization;
use App\Infrastructure\Persistence\Doctrine\Repository\User\UserRepository;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected function login(string $email = 'mathieu.marchois@beta.gouv.fr'): KernelBrowser
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail($email);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $organizations = [];
        $role = 'ROLE_USER';

        foreach ($testUser->getOrganizations() as $organization) {
            $organizations[] = $em->getReference(Organization::class, $organization->getUuid());

            if ($organization->getUuid() === 'e0d93630-acf7-4722-81e8-ff7d5fa64b66') {
                $role = 'ROLE_ADMIN';
            }
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
