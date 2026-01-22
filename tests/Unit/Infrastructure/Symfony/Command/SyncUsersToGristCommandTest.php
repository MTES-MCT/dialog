<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Infrastructure\CRM\GristClient;
use App\Infrastructure\Symfony\Command\SyncUsersToGristCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncUsersToGristCommandTest extends TestCase
{
    private OrganizationUserRepositoryInterface $organizationUserRepository;
    private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository;
    private GristClient $gristClient;
    private string $contactTableId;
    private string $organizationTableId;

    protected function setUp(): void
    {
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->regulationOrderHistoryRepository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);
        $this->gristClient = $this->createMock(GristClient::class);
        $this->contactTableId = 'test-contact-table-id';
        $this->organizationTableId = 'test-organization-table-id';
    }

    public function testExecuteWithNoUsers(): void
    {
        $this->organizationUserRepository
            ->expects(self::once())
            ->method('findAllUsersWithOrganizations')
            ->willReturn([]);

        $this->regulationOrderHistoryRepository
            ->expects(self::never())
            ->method('countCreatedRegulationOrdersByUserUuids');

        $this->gristClient
            ->expects(self::never())
            ->method('syncData');

        $this->gristClient
            ->expects(self::never())
            ->method('getRecords');

        $command = new SyncUsersToGristCommand(
            $this->organizationUserRepository,
            $this->regulationOrderHistoryRepository,
            $this->gristClient,
            $this->contactTableId,
            $this->organizationTableId,
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
        $org1Uuid = 'org-uuid-1';
        $org2Uuid = 'org-uuid-2';

        $rows = [
            [
                'userUuid' => $user1Uuid,
                'fullName' => 'Mathieu Marchois',
                'email' => 'mathieu@fairness.coop',
                'registrationDate' => new \DateTime('2024-01-15 10:30:00'),
                'lastActiveAt' => new \DateTime('2024-05-20 14:45:00'),
                'organizationUuid' => $org1Uuid,
                'organizationName' => 'Mairie de Savenay',
            ],
            [
                'userUuid' => $user1Uuid,
                'fullName' => 'Mathieu Marchois',
                'email' => 'mathieu@fairness.coop',
                'registrationDate' => new \DateTime('2024-01-15 10:30:00'),
                'lastActiveAt' => new \DateTime('2024-05-20 14:45:00'),
                'organizationUuid' => $org2Uuid,
                'organizationName' => 'EPCI Test',
            ],
            [
                'userUuid' => $user2Uuid,
                'fullName' => 'John Doe',
                'email' => 'john@example.com',
                'registrationDate' => new \DateTime('2024-02-10 09:00:00'),
                'lastActiveAt' => null,
                'organizationUuid' => $org1Uuid,
                'organizationName' => 'Mairie de Savenay',
            ],
        ];

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('findAllUsersWithOrganizations')
            ->willReturn($rows);

        $this->regulationOrderHistoryRepository
            ->expects(self::once())
            ->method('countCreatedRegulationOrdersByUserUuids')
            ->with(self::callback(fn (array $uuids) => \count($uuids) === 2 && \in_array($user1Uuid, $uuids, true) && \in_array($user2Uuid, $uuids, true)))
            ->willReturn([
                $user1Uuid => 8, // 5 + 3 (cumul toutes orgs)
                $user2Uuid => 0,
            ]);

        // Mock des organisations depuis Grist avec leurs IDs
        $this->gristClient
            ->expects(self::once())
            ->method('getRecords')
            ->with($this->organizationTableId)
            ->willReturn([
                [
                    'id' => 101,
                    'fields' => ['nom' => 'Mairie de Savenay'],
                ],
                [
                    'id' => 102,
                    'fields' => ['nom' => 'EPCI Test'],
                ],
            ]);

        $capturedRecords = [];
        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->with(self::anything(), $this->contactTableId)
            ->willReturnCallback(function (array $records) use (&$capturedRecords): void {
                $capturedRecords = $records;
            });

        $command = new SyncUsersToGristCommand(
            $this->organizationUserRepository,
            $this->regulationOrderHistoryRepository,
            $this->gristClient,
            $this->contactTableId,
            $this->organizationTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Synchronisation réussie : 2 utilisateur(s) synchronisé(s) avec Grist', $commandTester->getDisplay());

        // Vérifier les records envoyés
        $this->assertCount(2, $capturedRecords);

        $mathieuRecord = null;
        $johnRecord = null;
        foreach ($capturedRecords as $record) {
            if ($record['require']['email'] === 'mathieu@fairness.coop') {
                $mathieuRecord = $record;
            } elseif ($record['require']['email'] === 'john@example.com') {
                $johnRecord = $record;
            }
        }

        $this->assertNotNull($mathieuRecord);
        $this->assertNotNull($johnRecord);

        $this->assertSame('Mathieu Marchois', $mathieuRecord['fields']['full_name']);
        $this->assertSame('mathieu@fairness.coop', $mathieuRecord['fields']['email']);
        $this->assertSame('2024-01-15 10:30:00', $mathieuRecord['fields']['registration_date']);
        $this->assertSame('2024-05-20 14:45:00', $mathieuRecord['fields']['last_activity_date']);
        $this->assertSame(8, $mathieuRecord['fields']['created_regulation_orders_count']);
        // Les organisations sont formatées au format Grist : ["L", id1, id2, ...]
        $this->assertIsArray($mathieuRecord['fields']['organisations']);
        $this->assertSame('L', $mathieuRecord['fields']['organisations'][0]);
        $this->assertContains(101, $mathieuRecord['fields']['organisations']); // Mairie de Savenay
        $this->assertContains(102, $mathieuRecord['fields']['organisations']); // EPCI Test
        $this->assertCount(3, $mathieuRecord['fields']['organisations']); // ["L", 101, 102]

        $this->assertSame('John Doe', $johnRecord['fields']['full_name']);
        $this->assertSame('john@example.com', $johnRecord['fields']['email']);
        $this->assertIsArray($johnRecord['fields']['organisations']);
        $this->assertSame('L', $johnRecord['fields']['organisations'][0]);
        $this->assertContains(101, $johnRecord['fields']['organisations']); // Mairie de Savenay
        $this->assertCount(2, $johnRecord['fields']['organisations']); // ["L", 101]
        $this->assertSame('2024-02-10 09:00:00', $johnRecord['fields']['registration_date']);
        $this->assertNull($johnRecord['fields']['last_activity_date']);
        $this->assertSame(0, $johnRecord['fields']['created_regulation_orders_count']);
    }

    public function testExecuteWithError(): void
    {
        $userUuid = '9cebe00d-04d8-48da-89b1-059f6b7bfe44';
        $orgUuid = 'org-uuid-1';

        $rows = [
            [
                'userUuid' => $userUuid,
                'fullName' => 'Mathieu Marchois',
                'email' => 'mathieu@fairness.coop',
                'registrationDate' => new \DateTime('2024-01-15 10:30:00'),
                'lastActiveAt' => new \DateTime('2024-05-20 14:45:00'),
                'organizationUuid' => $orgUuid,
                'organizationName' => 'Mairie de Savenay',
            ],
        ];

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('findAllUsersWithOrganizations')
            ->willReturn($rows);

        $this->regulationOrderHistoryRepository
            ->expects(self::once())
            ->method('countCreatedRegulationOrdersByUserUuids')
            ->with([$userUuid])
            ->willReturn([
                $userUuid => 2,
            ]);

        // Mock des organisations depuis Grist
        $this->gristClient
            ->expects(self::once())
            ->method('getRecords')
            ->with($this->organizationTableId)
            ->willReturn([
                [
                    'id' => 101,
                    'fields' => ['nom' => 'Mairie de Savenay'],
                ],
            ]);

        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->willThrowException(new \RuntimeException('Grist API error'));

        $command = new SyncUsersToGristCommand(
            $this->organizationUserRepository,
            $this->regulationOrderHistoryRepository,
            $this->gristClient,
            $this->contactTableId,
            $this->organizationTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Erreur lors de la synchronisation : Grist API error', $commandTester->getDisplay());
    }
}
