<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\User\Command\SaveOrganizationCommand;
use App\Application\User\Command\SaveOrganizationCommandHandler;
use App\Domain\User\Exception\SiretAlreadyExistException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveOrganizationCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $dateUtils;
    private $organizationRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $date = new \DateTimeImmutable('2024-05-07');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdOrganization = (new Organization(uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0'))
            ->setCreatedAt($date)
            ->setSiret('21930027400012')
            ->setName('Commune de La Courneuve');

        $this->organizationRepository
            ->expects(self::once())
            ->method('add')
            ->with($createdOrganization);

        $this->organizationRepository
            ->expects(self::never())
            ->method('findOneBySiret');

        $handler = new SaveOrganizationCommandHandler(
            $this->idFactory,
            $this->dateUtils,
            $this->organizationRepository,
        );

        $command = new SaveOrganizationCommand();
        $command->siret = '21930027400012';
        $command->name = 'Commune de La Courneuve';

        $result = $handler($command);

        $this->assertEquals($createdOrganization, $result);
    }

    public function testUpdateWithSameSiret(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::exactly(2))
            ->method('getSiret')
            ->willReturn('21930027400012');
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Commune');
        $organization
            ->expects(self::once())
            ->method('update')
            ->with('Commune de La Courneuve', '21930027400012');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationRepository
            ->expects(self::never())
            ->method('findOneBySiret');

        $handler = new SaveOrganizationCommandHandler(
            $this->idFactory,
            $this->dateUtils,
            $this->organizationRepository,
        );

        $command = new SaveOrganizationCommand($organization);
        $command->siret = '21930027400012';
        $command->name = 'Commune de La Courneuve';

        $result = $handler($command);

        $this->assertEquals($organization, $result);
    }

    public function testUpdateWithDifferentSiret(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::exactly(2))
            ->method('getSiret')
            ->willReturn('21930027400012');
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Commune');
        $organization
            ->expects(self::once())
            ->method('update')
            ->with('Commune de La Courneuve', '11230056411113');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('11230056411113')
            ->willReturn(null);

        $handler = new SaveOrganizationCommandHandler(
            $this->idFactory,
            $this->dateUtils,
            $this->organizationRepository,
        );

        $command = new SaveOrganizationCommand($organization);
        $command->siret = '11230056411113';
        $command->name = 'Commune de La Courneuve';

        $result = $handler($command);

        $this->assertEquals($organization, $result);
    }

    public function testSiretAlreadyExist(): void
    {
        $this->expectException(SiretAlreadyExistException::class);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $organization2 = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::exactly(2))
            ->method('getSiret')
            ->willReturn('21930027400012');
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Commune');
        $organization
            ->expects(self::never())
            ->method('update');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('11230056411113')
            ->willReturn($organization2);

        $handler = new SaveOrganizationCommandHandler(
            $this->idFactory,
            $this->dateUtils,
            $this->organizationRepository,
        );

        $command = new SaveOrganizationCommand($organization);
        $command->siret = '11230056411113';
        $command->name = 'Commune de La Courneuve';

        $handler($command);
    }
}
