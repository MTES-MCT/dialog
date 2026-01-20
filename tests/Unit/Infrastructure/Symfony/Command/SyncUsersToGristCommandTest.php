<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\CRM\GristClient;
use App\Infrastructure\Symfony\Command\SyncUsersToGristCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncUsersToGristCommandTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private OrganizationUserRepositoryInterface $organizationUserRepository;
    private GristClient $gristClient;
    private string $contactTableId;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->gristClient = $this->createMock(GristClient::class);
        $this->contactTableId = 'test-contact-table-id';
    }

    public function testExecuteWithNoUsers(): void
    {
        $this->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        $this->gristClient
            ->expects(self::never())
            ->method('syncData');

        $command = new SyncUsersToGristCommand(
            $this->userRepository,
            $this->organizationUserRepository,
            $this->gristClient,
            $this->contactTableId,
        );

        $this->assertSame('app:grist:sync-users', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Aucun utilisateur à synchroniser', $commandTester->getDisplay());
    }

    public function testExecuteWithUsers(): void
    {
        $user1Uuid = '9cebe00d-04d8-48da-89b1-059f6b7bfe44';
        $user2Uuid = '2d3724f1-2910-48b4-ba56-81796f6e100b';

        $user1 = (new User($user1Uuid))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setRegistrationDate(new \DateTime('2024-01-15 10:30:00'))
            ->setLastActiveAt(new \DateTime('2024-05-20 14:45:00'));

        $user2 = (new User($user2Uuid))
            ->setFullName('John Doe')
            ->setEmail('john@example.com')
            ->setRegistrationDate(new \DateTime('2024-02-10 09:00:00'));

        $this->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$user1, $user2]);

        $org1 = new UserOrganizationView('org-uuid-1', 'Mairie de Savenay', true, []);
        $org2 = new UserOrganizationView('org-uuid-2', 'EPCI Test', true, []);

        $this->organizationUserRepository
            ->expects(self::exactly(2))
            ->method('findByUserUuid')
            ->willReturnCallback(fn (string $uuid) => match ($uuid) {
                $user1Uuid => [$org1, $org2],
                $user2Uuid => [],
                default => [],
            });

        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->with(
                [
                    [
                        'require' => ['email' => 'mathieu@fairness.coop'],
                        'fields' => [
                            'full_name' => 'Mathieu Marchois',
                            'email' => 'mathieu@fairness.coop',
                            'organisations' => 'Mairie de Savenay, EPCI Test',
                            'registration_date' => '2024-01-15 10:30:00',
                            'last_activity_date' => '2024-05-20 14:45:00',
                        ],
                    ],
                    [
                        'require' => ['email' => 'john@example.com'],
                        'fields' => [
                            'full_name' => 'John Doe',
                            'email' => 'john@example.com',
                            'organisations' => '',
                            'registration_date' => '2024-02-10 09:00:00',
                            'last_activity_date' => null,
                        ],
                    ],
                ],
                $this->contactTableId,
            );

        $command = new SyncUsersToGristCommand(
            $this->userRepository,
            $this->organizationUserRepository,
            $this->gristClient,
            $this->contactTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Synchronisation réussie : 2 utilisateur(s) synchronisé(s) avec Grist', $commandTester->getDisplay());
    }

    public function testExecuteWithError(): void
    {
        $userUuid = '9cebe00d-04d8-48da-89b1-059f6b7bfe44';

        $user = (new User($userUuid))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setRegistrationDate(new \DateTime('2024-01-15 10:30:00'))
            ->setLastActiveAt(new \DateTime('2024-05-20 14:45:00'));

        $this->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('findByUserUuid')
            ->with($userUuid)
            ->willReturn([]);

        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->willThrowException(new \RuntimeException('Grist API error'));

        $command = new SyncUsersToGristCommand(
            $this->userRepository,
            $this->organizationUserRepository,
            $this->gristClient,
            $this->contactTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Erreur lors de la synchronisation : Grist API error', $commandTester->getDisplay());
    }
}
