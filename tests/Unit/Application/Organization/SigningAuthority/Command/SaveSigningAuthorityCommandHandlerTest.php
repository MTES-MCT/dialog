<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\SigningAuthority\Command;

use App\Application\IdFactoryInterface;
use App\Application\Organization\SigningAuthority\Command\SaveSigningAuthorityCommand;
use App\Application\Organization\SigningAuthority\Command\SaveSigningAuthorityCommandHandler;
use App\Domain\Organization\SigningAuthority\Repository\SigningAuthorityRepositoryInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveSigningAuthorityCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $signingAuthorityRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->signingAuthorityRepository = $this->createMock(SigningAuthorityRepositoryInterface::class);
    }

    public function testAdd(): void
    {
        $organization = $this->createMock(Organization::class);

        $signingAuthority = new SigningAuthority(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Monsieur le maire de Savenay',
            address: '3 rue de la Concertation',
            placeOfSignature: 'Savenay',
            signatoryName: 'Monsieur X, Maire de Savenay',
            organization: $organization,
        );

        $this->signingAuthorityRepository
            ->expects(self::once())
            ->method('add')
            ->with($signingAuthority)
            ->willReturn($signingAuthority);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('9cebe00d-04d8-48da-89b1-059f6b7bfe44');

        $handler = new SaveSigningAuthorityCommandHandler(
            $this->idFactory,
            $this->signingAuthorityRepository,
        );
        $command = new SaveSigningAuthorityCommand($organization);
        $command->name = 'Monsieur le maire de Savenay';
        $command->address = '3 rue de la Concertation';
        $command->placeOfSignature = 'Savenay';
        $command->signatoryName = 'Monsieur X, Maire de Savenay';

        $handler($command);
    }

    public function testUpdate(): void
    {
        $organization = $this->createMock(Organization::class);
        $signingAuthority = $this->createMock(SigningAuthority::class);
        $signingAuthority
            ->expects(self::once())
            ->method('update')
            ->with(
                'Madame la maire de Savenay',
                '4 rue de la Concertation',
                'Savenay',
                'Madame X, Maire de Savenay',
            );

        $this->signingAuthorityRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $handler = new SaveSigningAuthorityCommandHandler(
            $this->idFactory,
            $this->signingAuthorityRepository,
        );

        $command = new SaveSigningAuthorityCommand($organization, $signingAuthority);
        $command->name = 'Madame la maire de Savenay';
        $command->address = '4 rue de la Concertation';
        $command->placeOfSignature = 'Savenay';
        $command->signatoryName = 'Madame X, Maire de Savenay';

        $handler($command);
    }
}
