<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\SaveOrganizationCommand;
use App\Application\Organization\Command\SaveOrganizationCommandHandler;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

class SaveOrganizationCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $command = new SaveOrganizationCommand();
        $command->name = 'Mairie de Maisons-Laffitte';

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);

        $idFactory
        ->expects(self::once())
        ->method('make')
        ->willReturn('fba4914e-eb54-464e-bbc9-92712374fe0a');

        $organizationRepository
        ->expects(self::once())
        ->method('save')
        ->with(new Organization('fba4914e-eb54-464e-bbc9-92712374fe0a', 'Mairie de Maisons-Laffitte'));

        $handler = new SaveOrganizationCommandHandler($idFactory, $organizationRepository);
        $handler->__invoke($command);
    }

    public function testUpdate(): void
    {
        $organization = $this->createMock(Organization::class);

        $organization
        ->expects(self::once())
        ->method('update')
        ->with('Mairie de Paris');

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);

        $idFactory
        ->expects(self::never())
        ->method('make');

        $organizationRepository
        ->expects(self::never())
        ->method('save');

        $command = new SaveOrganizationCommand();
        $command->organization = $organization;
        $command->name = 'Mairie de Paris';
        $handler = new SaveOrganizationCommandHandler($idFactory, $organizationRepository);
        $handler->__invoke($command);
    }
}
