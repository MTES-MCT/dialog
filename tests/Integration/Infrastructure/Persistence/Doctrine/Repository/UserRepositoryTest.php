<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserExportView;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepositoryTest extends AbstractWebTestCase
{
    public function testFindAllForExport(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $result = $userRepository->findAllForExport();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, \count($result));

        foreach ($result as $item) {
            $this->assertInstanceOf(UserExportView::class, $item);
            $this->assertIsString($item->fullName);
            $this->assertIsString($item->email);
            $this->assertNotEmpty($item->fullName);
            $this->assertNotEmpty($item->email);
        }

        $emails = array_map(fn (UserExportView $view) => $view->email, $result);
        $this->assertContains(UserFixture::DEPARTMENT_93_ADMIN_EMAIL, $emails);
        $this->assertContains(UserFixture::DEPARTMENT_93_USER_EMAIL, $emails);
    }

    public function testFindAllForExportReturnsValidEmailFormat(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $result = $userRepository->findAllForExport();

        foreach ($result as $item) {
            $this->assertStringContainsString('@', $item->email);
        }
    }

    public function testFindUsersToNotifyForInactivityExcludesFixtureUsers(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        // Tous les utilisateurs des fixtures sont soit non vérifiés,
        // soit membres d'une organisation ayant publié un arrêté.
        $result = $userRepository->findUsersToNotifyForInactivity(new \DateTimeImmutable('now'));

        $emails = array_map(fn (User $user) => $user->getEmail(), $result);
        $this->assertNotContains(UserFixture::DEPARTMENT_93_USER_EMAIL, $emails);
        $this->assertNotContains(UserFixture::DEPARTMENT_93_ADMIN_EMAIL, $emails);
        $this->assertNotContains(UserFixture::OTHER_ORG_USER_EMAIL, $emails);
    }

    public function testFindUsersToNotifyForInactivity(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        // Organisation sans aucun arrêté publié.
        $organization = (new Organization('11111111-1111-1111-1111-111111111111'))
            ->setName('Org inactive')
            ->setCreatedAt(new \DateTimeImmutable('2020-01-01'));

        $user = (new User('22222222-2222-2222-2222-222222222222'))
            ->setFullName('Inactive USER')
            ->setEmail('inactive@example.com')
            ->setRegistrationDate(new \DateTimeImmutable('2020-01-01'))
            ->setIsVerified();

        $organizationUser = (new OrganizationUser('33333333-3333-3333-3333-333333333333'))
            ->setUser($user)
            ->setOrganization($organization);

        $entityManager->persist($organization);
        $entityManager->persist($user);
        $entityManager->persist($organizationUser);
        $entityManager->flush();

        // Inscrit depuis longtemps : inéligible.
        $result = $userRepository->findUsersToNotifyForInactivity(new \DateTimeImmutable('2020-01-08'));
        $emails = array_map(fn (User $u) => $u->getEmail(), $result);
        $this->assertNotContains('inactive@example.com', $emails);

        // Le mail a déjà été envoyé : exclu.
        $user->setInactivityEmailSentAt(new \DateTimeImmutable('2020-01-08'));
        $entityManager->flush();
        $result = $userRepository->findUsersToNotifyForInactivity(new \DateTimeImmutable('2020-01-08'));
        $emails = array_map(fn (User $u) => $u->getEmail(), $result);
        $this->assertNotContains('inactive@example.com', $emails);
    }

    public function testFindUsersToNotifyForInactivityExcludesRecentRegistrations(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $organization = (new Organization('44444444-4444-4444-4444-444444444444'))
            ->setName('Org récente')
            ->setCreatedAt(new \DateTimeImmutable('2020-01-01'));

        $user = (new User('55555555-5555-5555-5555-555555555555'))
            ->setFullName('Recent USER')
            ->setEmail('recent@example.com')
            ->setRegistrationDate(new \DateTimeImmutable('2020-01-10'))
            ->setIsVerified();

        $organizationUser = (new OrganizationUser('66666666-6666-6666-6666-666666666666'))
            ->setUser($user)
            ->setOrganization($organization);

        $entityManager->persist($organization);
        $entityManager->persist($user);
        $entityManager->persist($organizationUser);
        $entityManager->flush();

        // Le seuil est antérieur à la date d'inscription : non encore éligible.
        $result = $userRepository->findUsersToNotifyForInactivity(new \DateTimeImmutable('2020-01-08'));
        $emails = array_map(fn (User $u) => $u->getEmail(), $result);
        $this->assertNotContains('recent@example.com', $emails);
    }
}
