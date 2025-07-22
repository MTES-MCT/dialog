<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\StorageInterface;
use App\Application\User\Command\SaveOrganizationCommand;
use App\Application\User\Command\SaveOrganizationCommandHandler;
use App\Domain\Organization\Establishment\Establishment;
use App\Domain\Organization\Establishment\Repository\EstablishmentRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveOrganizationCommandHandlerTest extends TestCase
{
    private $storage;

    private $idFactory;

    private $establishmentRepository;

    public function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->establishmentRepository = $this->createMock(EstablishmentRepositoryInterface::class);
    }

    public function testSave(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getLogo')
            ->willReturn('/path/to/logo.png');
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('496bd752-c217-4625-ba0c-7454dc218516');

        $this->storage
            ->expects(self::once())
            ->method('delete')
            ->with('/path/to/logo.png');

        $this->storage
            ->expects(self::once())
            ->method('write')
            ->with('organizations/496bd752-c217-4625-ba0c-7454dc218516', $file)
            ->willReturn('organizations/496bd752-c217-4625-ba0c-7454dc218516/logo.png');

        $organization
            ->expects(self::once())
            ->method('setLogo')
            ->with('organizations/496bd752-c217-4625-ba0c-7454dc218516/logo.png');

        $organization
            ->expects(self::once())
            ->method('update')
            ->with('Ville de Paris');

        $establishment = $this->createMock(Establishment::class);

        $establishment
            ->expects(self::once())
            ->method('update')
            ->with('123 Rue de la Paix', '75000', 'Paris', 'Appartement 1');

        $organization
            ->expects(self::exactly(2))
            ->method('getEstablishment')
            ->willReturn($establishment);

        $handler = new SaveOrganizationCommandHandler(
            $this->storage,
            $this->idFactory,
            $this->establishmentRepository,
        );

        $command = new SaveOrganizationCommand($organization);
        $command->file = $file;
        $command->name = 'Ville de Paris';
        $command->address = '123 Rue de la Paix';
        $command->zipCode = '75000';
        $command->city = 'Paris';
        $command->addressComplement = 'Appartement 1';

        $result = $handler($command);

        $this->assertEquals($organization, $result);
    }

    public function testSaveNewEstablishment(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::never())
            ->method('getLogo');
        $organization
            ->expects(self::never())
            ->method('getUuid');

        $this->storage
            ->expects(self::never())
            ->method('delete');

        $this->storage
            ->expects(self::never())
            ->method('write');

        $organization
            ->expects(self::never())
            ->method('setLogo');

        $organization
            ->expects(self::once())
            ->method('update')
            ->with('Ville de Paris');

        $organization
            ->expects(self::exactly(2))
            ->method('getEstablishment')
            ->willReturn(null);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('123e4567-e89b-12d3-a456-426614174000');

        $establishment = new Establishment(
            uuid: '123e4567-e89b-12d3-a456-426614174000',
            address: '123 Rue de la Paix',
            zipCode: '75000',
            city: 'Paris',
            organization: $organization,
            addressComplement: 'Appartement 1',
        );

        $this->establishmentRepository
            ->expects(self::once())
            ->method('add')
            ->with($establishment);

        $organization
            ->expects(self::once())
            ->method('setEstablishment')
            ->with($establishment);

        $handler = new SaveOrganizationCommandHandler(
            $this->storage,
            $this->idFactory,
            $this->establishmentRepository,
        );

        $command = new SaveOrganizationCommand($organization);
        $command->name = 'Ville de Paris';
        $command->address = '123 Rue de la Paix';
        $command->zipCode = '75000';
        $command->city = 'Paris';
        $command->addressComplement = 'Appartement 1';

        $result = $handler($command);

        $this->assertEquals($organization, $result);
    }
}
