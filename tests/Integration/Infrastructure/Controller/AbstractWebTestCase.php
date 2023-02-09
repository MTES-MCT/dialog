<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Infrastructure\Persistence\Doctrine\Repository\User\UserRepository;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected function login(string $email = 'mathieu.marchois@beta.gouv.fr'): KernelBrowser
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail($email);
        $client->loginUser(
            new SymfonyUser(
                $testUser->getUuid(),
                $testUser->getEmail(),
                $testUser->getFullName(),
                $testUser->getPassword(),
                $testUser->getOrganizationUuids(),
                ['ROLE_USER'],
            )
        );

        return $client;
    }

    protected function assertSecurityHeaders(): void
    {
        $this->assertResponseHeaderSame('X-XSS-Protection', '1; mode=block');
        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertResponseHasHeader('X-Content-Security-Policy');
        $this->assertResponseHasHeader('Content-Security-Policy');
    }
}
