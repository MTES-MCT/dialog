<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\User\Command\CreateOrganizationCommand;
use App\Application\User\Command\CreateOrganizationCommandHandler;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateOrganizationCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $organizationRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdOrganization = (new Organization(uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0'))
            ->setSiret('21930027400012')
            ->setName('Commune de La Courneuve');

        $this->organizationRepository
            ->expects(self::once())
            ->method('add')
            ->with($createdOrganization);

        $handler = new CreateOrganizationCommandHandler(
            $this->idFactory,
            $this->organizationRepository,
        );

        $command = new CreateOrganizationCommand();
        $command->siret = '21930027400012';
        $command->name = 'Commune de La Courneuve';

        $result = $handler($command);

        $this->assertEquals($createdOrganization, $result);
    }
}
