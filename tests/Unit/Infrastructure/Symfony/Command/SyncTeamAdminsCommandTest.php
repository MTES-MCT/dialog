<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Symfony\Command\SyncTeamAdminsCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class SyncTeamAdminsCommandTest extends TestCase
{
    private const ORG_UUID = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';

    private MockObject&UserRepositoryInterface $userRepository;
    private MockObject&PasswordUserRepositoryInterface $passwordUserRepository;
    private MockObject&OrganizationUserRepositoryInterface $organizationUserRepository;
    private MockObject&OrganizationRepositoryInterface $organizationRepository;
    private MockObject&IdFactoryInterface $idFactory;
    private MockObject&DateUtilsInterface $dateUtils;
    private MockObject&StringUtilsInterface $stringUtils;
    private MockObject&EntityManagerInterface $entityManager;

    private string $projectDir;
    private string $configPath;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordUserRepository = $this->createMock(PasswordUserRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->stringUtils->method('normalizeEmail')->willReturnCallback(
            static fn (string $email): string => strtolower(trim($email)),
        );

        $this->projectDir = sys_get_temp_dir() . '/dialog-team-admins-' . uniqid();
        mkdir($this->projectDir . '/config', 0o777, true);
        $this->configPath = $this->projectDir . '/config/team_admins.yaml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->configPath)) {
            unlink($this->configPath);
        }
        if (is_dir($this->projectDir . '/config')) {
            rmdir($this->projectDir . '/config');
        }
        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
    }

    private function writeConfig(?array $admins): void
    {
        $payload = [];
        if (null !== $admins) {
            $payload['admins'] = $admins;
        }
        file_put_contents($this->configPath, Yaml::dump($payload));
    }

    private function createCommand(string $dialogOrgId = self::ORG_UUID): SyncTeamAdminsCommand
    {
        return new SyncTeamAdminsCommand(
            $this->userRepository,
            $this->passwordUserRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->idFactory,
            $this->dateUtils,
            $this->stringUtils,
            $this->entityManager,
            $this->projectDir,
            $dialogOrgId,
        );
    }

    public function testHasExpectedName(): void
    {
        $this->assertSame('app:team:sync-admins', $this->createCommand()->getName());
    }

    public function testFailsWhenConfigFileMissing(): void
    {
        $this->organizationRepository->expects(self::never())->method('findOneByUuid');
        $this->entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString('Fichier de configuration introuvable', $tester->getDisplay());
    }

    public function testFailsWhenOrganizationNotFoundInDatabase(): void
    {
        $this->writeConfig([]);
        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with(self::ORG_UUID)
            ->willReturn(null);
        $this->entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString('Organisation équipe introuvable', $tester->getDisplay());
    }

    public function testFailsWhenAdminEntryIsInvalid(): void
    {
        $this->writeConfig([['email' => 'no-name@beta.gouv.fr']]);
        $this->organizationRepository->method('findOneByUuid')->willReturn($this->makeOrganization());
        $this->entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString('`email` et `full_name` sont requis', $tester->getDisplay());
    }

    public function testFailsOnDuplicateEmail(): void
    {
        $this->writeConfig([
            ['email' => 'dup@beta.gouv.fr', 'full_name' => 'A'],
            ['email' => 'DUP@beta.gouv.fr', 'full_name' => 'B'],
        ]);
        $this->organizationRepository->method('findOneByUuid')->willReturn($this->makeOrganization());
        $this->entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $statusCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString('Email dupliqué', $tester->getDisplay());
    }

    public function testCreatesNewAdminWhenPasswordHashProvided(): void
    {
        $this->writeConfig([
            [
                'email' => 'new@beta.gouv.fr',
                'full_name' => 'New ADMIN',
                'password_hash' => '$2y$13$hash',
            ],
        ]);

        $organization = $this->makeOrganization();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00');

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository
            ->expects(self::once())
            ->method('findAllByRole')
            ->with(UserRolesEnum::ROLE_SUPER_ADMIN->value)
            ->willReturn([]);
        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('new@beta.gouv.fr')
            ->willReturn(null);
        $this->dateUtils->method('getNow')->willReturn($now);
        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturnOnConsecutiveCalls('uuid-user', 'uuid-password', 'uuid-orgu');

        $createdUser = null;
        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->willReturnCallback(function (User $user) use (&$createdUser) {
                $createdUser = $user;

                return $user;
            });

        $this->passwordUserRepository->expects(self::once())->method('add');
        $this->organizationUserRepository->expects(self::once())->method('add');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->assertNotNull($createdUser);
        $this->assertSame('new@beta.gouv.fr', $createdUser?->getEmail());
        $this->assertSame('New ADMIN', $createdUser?->getFullName());
        $this->assertSame([UserRolesEnum::ROLE_SUPER_ADMIN->value], $createdUser?->getRoles());
        $this->assertTrue($createdUser?->isVerified());
        $this->assertSame($now, $createdUser?->getRegistrationDate());

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[create]', $output);
        $this->assertStringContainsString('créés: 1', $output);
    }

    public function testSkipsCreationWhenPasswordHashMissing(): void
    {
        $this->writeConfig([
            ['email' => 'ghost@beta.gouv.fr', 'full_name' => 'Ghost'],
        ]);
        $this->organizationRepository->method('findOneByUuid')->willReturn($this->makeOrganization());
        $this->userRepository->method('findAllByRole')->willReturn([]);
        $this->userRepository->method('findOneByEmail')->willReturn(null);

        $this->userRepository->expects(self::never())->method('add');
        $this->passwordUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[skip]', $output);
        $this->assertStringContainsString('ignorés: 1', $output);
    }

    public function testPromotesExistingNonAdminUser(): void
    {
        $this->writeConfig([
            ['email' => 'existing@beta.gouv.fr', 'full_name' => 'Existing USER'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser('user-uuid', 'existing@beta.gouv.fr', 'Old Name', [UserRolesEnum::ROLE_USER->value], isVerified: false);

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([]);
        $this->userRepository->method('findOneByEmail')->with('existing@beta.gouv.fr')->willReturn($existing);

        $this->organizationUserRepository->method('findOrganizationUser')->willReturn(null);
        $this->idFactory->expects(self::once())->method('make')->willReturn('uuid-orgu');

        $this->userRepository->expects(self::never())->method('add');
        $this->passwordUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::once())->method('add');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->assertContains(UserRolesEnum::ROLE_SUPER_ADMIN->value, $existing->getRoles());
        $this->assertSame('Existing USER', $existing->getFullName());
        $this->assertTrue($existing->isVerified());
        $this->assertStringContainsString('[promote]', $tester->getDisplay());
    }

    public function testUpdatesExistingSuperAdminFullNameAndDoesNotTouchPassword(): void
    {
        $this->writeConfig([
            ['email' => 'admin@beta.gouv.fr', 'full_name' => 'Renamed ADMIN'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser(
            'user-uuid',
            'admin@beta.gouv.fr',
            'Old Name',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$existing]);

        $this->organizationUserRepository
            ->method('findOrganizationUser')
            ->with(self::ORG_UUID, 'user-uuid')
            ->willReturn(new OrganizationUser('orgu-existing'));

        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->passwordUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->assertSame('Renamed ADMIN', $existing->getFullName());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('[update]', $output);
        $this->assertStringContainsString('mis à jour: 1', $output);
    }

    public function testNoopWhenSuperAdminMatchesConfigExactly(): void
    {
        $this->writeConfig([
            ['email' => 'admin@beta.gouv.fr', 'full_name' => 'Same NAME'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser(
            'user-uuid',
            'admin@beta.gouv.fr',
            'Same NAME',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$existing]);
        $this->organizationUserRepository
            ->method('findOrganizationUser')
            ->willReturn(new OrganizationUser('orgu-existing'));

        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->organizationUserRepository->expects(self::never())->method('add');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('créés: 0, mis à jour: 0, supprimés: 0, ignorés: 0', $output);
        $this->assertStringNotContainsString('[update]', $output);
    }

    public function testStrictSyncDeletesSuperAdminAbsentFromConfig(): void
    {
        $this->writeConfig([]);
        $organization = $this->makeOrganization();
        $obsolete = $this->makeUser(
            'user-uuid',
            'gone@beta.gouv.fr',
            'Gone PERSON',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$obsolete]);

        $this->userRepository
            ->expects(self::once())
            ->method('remove')
            ->with($obsolete);
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[delete]', $output);
        $this->assertStringContainsString('supprimés: 1', $output);
    }

    public function testDryRunPerformsNoWritesAndDoesNotFlush(): void
    {
        $this->writeConfig([
            ['email' => 'new@beta.gouv.fr', 'full_name' => 'New', 'password_hash' => '$2y$13$h'],
        ]);
        $organization = $this->makeOrganization();
        $obsolete = $this->makeUser(
            'old-uuid',
            'old@beta.gouv.fr',
            'Old',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$obsolete]);
        $this->userRepository->method('findOneByEmail')->willReturn(null);

        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->passwordUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->entityManager->expects(self::never())->method('flush');
        $this->idFactory->expects(self::never())->method('make');

        $tester = new CommandTester($this->createCommand());
        $tester->execute(['--dry-run' => true]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[create]', $output);
        $this->assertStringContainsString('[delete]', $output);
        $this->assertStringContainsString('(dry-run)', $output);
    }

    public function testCustomConfigOptionOverridesDefaultPath(): void
    {
        $custom = sys_get_temp_dir() . '/team_admins_custom_' . uniqid() . '.yaml';
        file_put_contents($custom, Yaml::dump([
            'admins' => [],
        ]));

        try {
            $this->organizationRepository->method('findOneByUuid')->willReturn($this->makeOrganization());
            $this->userRepository->method('findAllByRole')->willReturn([]);

            $tester = new CommandTester($this->createCommand());
            $statusCode = $tester->execute(['--config' => $custom]);
            $this->assertSame(Command::SUCCESS, $statusCode);
        } finally {
            unlink($custom);
        }
    }

    public function testPromoteWithDryRunDoesNotModifyUser(): void
    {
        $this->writeConfig([
            ['email' => 'user@beta.gouv.fr', 'full_name' => 'Updated Name'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser('user-uuid', 'user@beta.gouv.fr', 'Old Name', [UserRolesEnum::ROLE_USER->value], isVerified: false);

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([]);
        $this->userRepository->method('findOneByEmail')->with('user@beta.gouv.fr')->willReturn($existing);

        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('findOrganizationUser');
        $this->entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute(['--dry-run' => true]);
        $tester->assertCommandIsSuccessful();

        $this->assertNotContains(UserRolesEnum::ROLE_SUPER_ADMIN->value, $existing->getRoles());
        $this->assertSame('Old Name', $existing->getFullName());
        $this->assertFalse($existing->isVerified());
        $this->assertStringContainsString('[promote]', $tester->getDisplay());
        $this->assertStringContainsString('(dry-run)', $tester->getDisplay());
    }

    public function testUpdateNonVerifiedUserToVerified(): void
    {
        $this->writeConfig([
            ['email' => 'admin@beta.gouv.fr', 'full_name' => 'Admin'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser(
            'user-uuid',
            'admin@beta.gouv.fr',
            'Admin',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: false,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$existing]);
        $this->organizationUserRepository
            ->method('findOrganizationUser')
            ->willReturn(new OrganizationUser('orgu-existing'));

        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->assertTrue($existing->isVerified());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('[update]', $output);
        $this->assertStringContainsString('is_verified → true', $output);
    }

    public function testUpdateUserWithoutRoleToHaveRole(): void
    {
        $this->writeConfig([
            ['email' => 'admin@beta.gouv.fr', 'full_name' => 'Admin'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser(
            'user-uuid',
            'admin@beta.gouv.fr',
            'Admin',
            [UserRolesEnum::ROLE_USER->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$existing]);
        $this->organizationUserRepository
            ->method('findOrganizationUser')
            ->willReturn(new OrganizationUser('orgu-existing'));

        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->assertContains(UserRolesEnum::ROLE_SUPER_ADMIN->value, $existing->getRoles());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('[update]', $output);
        $this->assertStringContainsString('ROLE_SUPER_ADMIN', $output);
    }

    public function testUpdateWithoutOrgLinkCreatesLink(): void
    {
        $this->writeConfig([
            ['email' => 'admin@beta.gouv.fr', 'full_name' => 'Admin'],
        ]);

        $organization = $this->makeOrganization();
        $existing = $this->makeUser(
            'user-uuid',
            'admin@beta.gouv.fr',
            'Admin',
            [UserRolesEnum::ROLE_SUPER_ADMIN->value],
            isVerified: true,
        );

        $this->organizationRepository->method('findOneByUuid')->willReturn($organization);
        $this->userRepository->method('findAllByRole')->willReturn([$existing]);
        $this->organizationUserRepository
            ->method('findOrganizationUser')
            ->willReturn(null);

        $this->idFactory->expects(self::once())->method('make')->willReturn('uuid-orgu');
        $this->organizationUserRepository->expects(self::once())->method('add');
        $this->userRepository->expects(self::never())->method('add');
        $this->userRepository->expects(self::never())->method('remove');
        $this->entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[update]', $output);
        $this->assertStringContainsString('link to team organization', $output);
    }

    private function makeOrganization(): Organization
    {
        $organization = new Organization(self::ORG_UUID);
        $organization->setName('DiaLog');

        return $organization;
    }

    private function makeUser(
        string $uuid,
        string $email,
        string $fullName,
        array $roles,
        bool $isVerified,
    ): User {
        $user = (new User($uuid))
            ->setEmail($email)
            ->setFullName($fullName)
            ->setRoles($roles)
            ->setRegistrationDate(new \DateTimeImmutable('2024-01-01'));
        if ($isVerified) {
            $user->setIsVerified();
        }

        return $user;
    }
}
