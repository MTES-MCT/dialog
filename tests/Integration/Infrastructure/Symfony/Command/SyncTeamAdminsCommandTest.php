<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Infrastructure\Symfony\Command\SyncTeamAdminsCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class SyncTeamAdminsCommandTest extends KernelTestCase
{
    private string $tmpConfigPath;

    protected function setUp(): void
    {
        $this->tmpConfigPath = tempnam(sys_get_temp_dir(), 'team_admins_') . '.yaml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->tmpConfigPath)) {
            unlink($this->tmpConfigPath);
        }
    }

    private function writeConfig(array $admins): void
    {
        file_put_contents($this->tmpConfigPath, Yaml::dump([
            'admins' => $admins,
        ]));
    }

    private function runCommand(array $options = []): CommandTester
    {
        $container = static::getContainer();
        $command = $container->get(SyncTeamAdminsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(array_merge(['--config' => $this->tmpConfigPath], $options));

        return $tester;
    }

    public function testPromoteAndLinkExistingUser(): void
    {
        self::bootKernel();
        $this->writeConfig([
            [
                'email' => UserFixture::DEPARTMENT_93_ADMIN_EMAIL,
                'full_name' => 'Mathieu FERNANDEZ',
            ],
        ]);

        $tester = $this->runCommand();
        $tester->assertCommandIsSuccessful();

        $container = static::getContainer();
        $userRepo = $container->get(UserRepositoryInterface::class);
        $orgUserRepo = $container->get(OrganizationUserRepositoryInterface::class);

        $user = $userRepo->findOneByEmail(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $this->assertNotNull($user);
        $this->assertContains(UserRolesEnum::ROLE_SUPER_ADMIN->value, $user->getRoles());
        $this->assertNotNull(
            $orgUserRepo->findOrganizationUser(OrganizationFixture::DIALOG_ORG_ID, $user->getUuid()),
            'L\'utilisateur doit être rattaché à l\'organisation équipe.',
        );
    }

    public function testCreateMissingAdminWithPasswordHash(): void
    {
        self::bootKernel();
        $email = 'new.team.member@beta.gouv.fr';
        $this->writeConfig([
            [
                'email' => UserFixture::DEPARTMENT_93_ADMIN_EMAIL,
                'full_name' => 'Mathieu FERNANDEZ',
            ],
            [
                'email' => $email,
                'full_name' => 'New MEMBER',
                'password_hash' => '$2y$13$FGzLfRBW0sGZqn/eh3hlBO/nQI4XZKzrzI1svPbCYUKWlRrQELCPm',
            ],
        ]);

        $tester = $this->runCommand();
        $tester->assertCommandIsSuccessful();

        $userRepo = static::getContainer()->get(UserRepositoryInterface::class);
        $created = $userRepo->findOneByEmail($email);
        $this->assertNotNull($created);
        $this->assertSame('New MEMBER', $created->getFullName());
        $this->assertTrue($created->isVerified());
        $this->assertContains(UserRolesEnum::ROLE_SUPER_ADMIN->value, $created->getRoles());
        $this->assertNotNull($created->getPasswordUser());
    }

    public function testSkipCreationWhenPasswordHashMissing(): void
    {
        self::bootKernel();
        $email = 'ghost.member@beta.gouv.fr';
        $this->writeConfig([
            [
                'email' => UserFixture::DEPARTMENT_93_ADMIN_EMAIL,
                'full_name' => 'Mathieu FERNANDEZ',
            ],
            [
                'email' => $email,
                'full_name' => 'Ghost MEMBER',
            ],
        ]);

        $tester = $this->runCommand();
        $tester->assertCommandIsSuccessful();

        $userRepo = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertNull($userRepo->findOneByEmail($email));
        $this->assertStringContainsString('[skip]', $tester->getDisplay());
    }

    public function testStrictSyncDeletesAdminsAbsentFromConfig(): void
    {
        self::bootKernel();
        $this->writeConfig([]);

        $tester = $this->runCommand();
        $tester->assertCommandIsSuccessful();

        $userRepo = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertNull(
            $userRepo->findOneByEmail(UserFixture::DEPARTMENT_93_ADMIN_EMAIL),
            'Un super-admin absent du YAML doit être supprimé.',
        );
    }

    public function testDryRunDoesNotWriteAnything(): void
    {
        self::bootKernel();
        $this->writeConfig([]);

        $tester = $this->runCommand(['--dry-run' => true]);
        $tester->assertCommandIsSuccessful();

        $userRepo = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertNotNull(
            $userRepo->findOneByEmail(UserFixture::DEPARTMENT_93_ADMIN_EMAIL),
            'En dry-run, aucune suppression ne doit être effectuée.',
        );
        $this->assertStringContainsString('dry-run', $tester->getDisplay());
    }

    public function testFailsWhenConfigMissing(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $command = $container->get(SyncTeamAdminsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--config' => '/nonexistent/path.yaml']);

        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testUpdateUserWithoutOrganizationLink(): void
    {
        self::bootKernel();
        $this->writeConfig([
            [
                'email' => UserFixture::DEPARTMENT_93_ADMIN_EMAIL,
                'full_name' => 'Updated ADMIN',
            ],
        ]);

        $container = static::getContainer();
        $orgUserRepo = $container->get(OrganizationUserRepositoryInterface::class);
        $userRepo = $container->get(UserRepositoryInterface::class);

        $user = $userRepo->findOneByEmail(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $this->assertNotNull($user);

        $existingLink = $orgUserRepo->findOrganizationUser(OrganizationFixture::DIALOG_ORG_ID, $user->getUuid());
        if ($existingLink !== null) {
            $orgUserRepo->remove($existingLink);
            static::getContainer()->get('doctrine.orm.default_entity_manager')->flush();
        }

        $tester = $this->runCommand();
        $tester->assertCommandIsSuccessful();

        $this->assertNotNull(
            $orgUserRepo->findOrganizationUser(OrganizationFixture::DIALOG_ORG_ID, $user->getUuid()),
            'L\'utilisateur doit être rattaché à l\'organisation équipe après mise à jour.',
        );
        $this->assertStringContainsString('link to team organization', $tester->getDisplay());
    }
}
