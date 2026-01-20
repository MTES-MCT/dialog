<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\CRM\GristClient;
use App\Infrastructure\Symfony\Command\SyncOrganizationsToGristCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncOrganizationsToGristCommandTest extends TestCase
{
    private OrganizationRepositoryInterface $organizationRepository;
    private GristClient $gristClient;
    private string $organizationTableId;

    protected function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->gristClient = $this->createMock(GristClient::class);
        $this->organizationTableId = 'test-organization-table-id';
    }

    public function testExecuteWithNoOrganizations(): void
    {
        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllEntities')
            ->willReturn([]);

        $this->gristClient
            ->expects(self::never())
            ->method('syncData');

        $command = new SyncOrganizationsToGristCommand(
            $this->organizationRepository,
            $this->gristClient,
            $this->organizationTableId,
        );

        $this->assertSame('app:grist:sync-organizations', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Aucune organisation à synchroniser', $commandTester->getDisplay());
    }

    public function testExecuteWithOrganizations(): void
    {
        $org1 = (new Organization('6598fd41-85cb-42a6-9693-1bc45f4dd392'))
            ->setName('Mairie de Savenay')
            ->setSiret('22930008201453')
            ->setCode('44260')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value)
            ->setDepartmentCode('44');

        $org2 = (new Organization('2d3724f1-2910-48b4-ba56-81796f6e100b'))
            ->setName('EPCI Test')
            ->setSiret('20001234567890')
            ->setCode('200012345')
            ->setCodeType(OrganizationCodeTypeEnum::EPCI->value)
            ->setDepartmentCode('75');

        $org3 = (new Organization('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setName('Département de la Loire-Atlantique')
            ->setSiret('22440001200010')
            ->setCode('44')
            ->setCodeType(OrganizationCodeTypeEnum::DEPARTMENT->value)
            ->setDepartmentCode('44');

        $org4 = (new Organization('133fb411-7754-4749-9590-ce05a2abe108'))
            ->setName('Région Pays de la Loire')
            ->setSiret('22520001600010')
            ->setCode('52')
            ->setCodeType(OrganizationCodeTypeEnum::REGION->value)
            ->setDepartmentCode(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllEntities')
            ->willReturn([$org1, $org2, $org3, $org4]);

        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->with(
                [
                    [
                        'require' => ['siret' => '22930008201453'],
                        'fields' => [
                            'nom' => 'Mairie de Savenay',
                            'type' => 'Commune',
                            'departement' => '44',
                            'siret' => '22930008201453',
                            'code_insee' => '44260',
                        ],
                    ],
                    [
                        'require' => ['siret' => '20001234567890'],
                        'fields' => [
                            'nom' => 'EPCI Test',
                            'type' => 'EPCI',
                            'departement' => '75',
                            'siret' => '20001234567890',
                            'code_insee' => '200012345',
                        ],
                    ],
                    [
                        'require' => ['siret' => '22440001200010'],
                        'fields' => [
                            'nom' => 'Département de la Loire-Atlantique',
                            'type' => 'Département',
                            'departement' => '44',
                            'siret' => '22440001200010',
                            'code_insee' => '44',
                        ],
                    ],
                    [
                        'require' => ['siret' => '22520001600010'],
                        'fields' => [
                            'nom' => 'Région Pays de la Loire',
                            'type' => 'Région',
                            'departement' => null,
                            'siret' => '22520001600010',
                            'code_insee' => '52',
                        ],
                    ],
                ],
                $this->organizationTableId,
            );

        $command = new SyncOrganizationsToGristCommand(
            $this->organizationRepository,
            $this->gristClient,
            $this->organizationTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Synchronisation réussie : 4 organisation(s) synchronisée(s) avec Grist', $commandTester->getDisplay());
    }

    public function testExecuteWithError(): void
    {
        $org = (new Organization('6598fd41-85cb-42a6-9693-1bc45f4dd392'))
            ->setName('Mairie de Savenay')
            ->setSiret('22930008201453')
            ->setCode('44260')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value)
            ->setDepartmentCode('44');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllEntities')
            ->willReturn([$org]);

        $this->gristClient
            ->expects(self::once())
            ->method('syncData')
            ->willThrowException(new \RuntimeException('Grist API error'));

        $command = new SyncOrganizationsToGristCommand(
            $this->organizationRepository,
            $this->gristClient,
            $this->organizationTableId,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Erreur lors de la synchronisation : Grist API error', $commandTester->getDisplay());
    }
}
