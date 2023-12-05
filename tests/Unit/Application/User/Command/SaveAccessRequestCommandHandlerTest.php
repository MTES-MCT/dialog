<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\SaveAccessRequestCommand;
use App\Application\User\Command\SaveAccessRequestCommandHandler;
use App\Domain\User\AccessRequest;
use App\Domain\User\Exception\AccessAlreadyRequestedException;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Specification\IsAccessAlreadyRequested;
use PHPUnit\Framework\TestCase;

final class SaveAccessRequestCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $consentContact = false;
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $accessRequestRepository = $this->createMock(AccessRequestRepositoryInterface::class);
        $isAccessAlreadyRequested = $this->createMock(IsAccessAlreadyRequested::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $stringUtils = $this->createMock(StringUtilsInterface::class);

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $isAccessAlreadyRequested
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu@fairness.coop')
            ->willReturn(false);

        $passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('password')
            ->willReturn('passwordHashed');

        $stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with('  mathiEu@fairness.cOop  ')
            ->willReturn('mathieu@fairness.coop');

        $accessRequest = new AccessRequest(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            comment: 'Test comment',
            consentToBeContacted: $consentContact,
            fullName: 'Mathieu Marchois',
            email: 'mathieu@fairness.coop',
            siret: '82050375300015',
            password: 'passwordHashed',
            organization: 'Fairness',
        );

        $accessRequestRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($accessRequest))
        ;

        $handler = new SaveAccessRequestCommandHandler(
            $idFactory,
            $accessRequestRepository,
            $isAccessAlreadyRequested,
            $passwordHasher,
            $stringUtils,
        );
        $command = new SaveAccessRequestCommand();
        $command->comment = 'Test comment';
        $command->consentToBeContacted = $consentContact;
        $command->fullName = 'Mathieu Marchois';
        $command->email = '  mathiEu@fairness.cOop  '; // Will be normalized
        $command->organizationName = 'Fairness';
        $command->organizationSiret = '82050375300015';
        $command->password = 'password';

        $handler($command);
    }

    public function testAlreadyRequested(): void
    {
        $this->expectException(AccessAlreadyRequestedException::class);

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $accessRequestRepository = $this->createMock(AccessRequestRepositoryInterface::class);
        $isAccessAlreadyRequested = $this->createMock(IsAccessAlreadyRequested::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $stringUtils = $this->createMock(StringUtilsInterface::class);

        $idFactory
            ->expects(self::never())
            ->method('make');

        $isAccessAlreadyRequested
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu@fairness.coop')
            ->willReturn(true);

        $accessRequestRepository
            ->expects(self::never())
            ->method('add');

        $passwordHasher
            ->expects(self::never())
            ->method('hash');

        $stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with('  mathiEu@fairness.cOop  ')
            ->willReturn('mathieu@fairness.coop');

        $handler = new SaveAccessRequestCommandHandler(
            $idFactory,
            $accessRequestRepository,
            $isAccessAlreadyRequested,
            $passwordHasher,
            $stringUtils,
        );
        $command = new SaveAccessRequestCommand();
        $command->comment = 'Test comment';
        $command->consentToBeContacted = true;
        $command->fullName = 'Mathieu Marchois';
        $command->email = '  mathiEu@fairness.cOop  ';
        $command->organizationName = 'Fairness';
        $command->organizationSiret = '82050375300015';
        $command->password = 'password';

        $handler($command);
    }
}
