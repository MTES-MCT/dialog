<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\User\Command\ConvertAccessRequestToUserCommand;
use App\Application\User\Command\ConvertAccessRequestToUserCommandHandler;
use App\Domain\User\AccessRequest;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\AccessRequestNotFoundException;
use App\Domain\User\Exception\SiretMissingException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConvertAccessRequestToUserCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $accessRequestRepository;
    private MockObject $userRepository;
    private MockObject $organizationUserRepository;
    private MockObject $organizationRepository;
    private MockObject $accessRequest;
    private MockObject $dateUtils;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->accessRequestRepository = $this->createMock(AccessRequestRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->accessRequest = $this->createMock(AccessRequest::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testConvertWithSiretLinkedToExistingOrganization(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->accessRequest
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $this->accessRequest
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('mathieu@fairness.coop');
        $this->accessRequest
            ->expects(self::once())
            ->method('getPassword')
            ->willReturn('passwordHashed');
        $this->accessRequest
            ->expects(self::exactly(2))
            ->method('getSiret')
            ->willReturn('82050375300015');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('82050375300015')
            ->willReturn($organization);

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23')
            ->willReturn($this->accessRequest);

        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                '0de5692b-cab1-494c-804d-765dc14df674',
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
            );

        $date = new \DateTimeImmutable('2024-05-07');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $user = (new User('0de5692b-cab1-494c-804d-765dc14df674'))
            ->setFullName('Mathieu MARCHOIS')
            ->setPassword('passwordHashed')
            ->setEmail('mathieu@fairness.coop')
            ->setRegistrationDate($date)
            ->setRoles([UserRolesEnum::ROLE_USER->value]);

        $organizationUser = (new OrganizationUser('f40f95eb-a7dd-4232-9f03-2db10f04f37f'))
            ->setOrganization($organization)
            ->setUser($user)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($organizationUser));

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($user));

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->equalTo($this->accessRequest));

        $handler = new ConvertAccessRequestToUserCommandHandler(
            $this->idFactory,
            $this->accessRequestRepository,
            $this->userRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
        );
        $command = new ConvertAccessRequestToUserCommand('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23');
        $handler($command);
    }

    public function testConvertWithSiretNotLinkedToAnOrganization(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->accessRequest
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $this->accessRequest
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('mathieu@fairness.coop');
        $this->accessRequest
            ->expects(self::once())
            ->method('getPassword')
            ->willReturn('passwordHashed');
        $this->accessRequest
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn('Fairness');
        $this->accessRequest
            ->expects(self::exactly(3))
            ->method('getSiret')
            ->willReturn('82050375300015');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('82050375300015')
            ->willReturn(null);

        $date = new \DateTimeImmutable('2024-05-07');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $user = (new User('0de5692b-cab1-494c-804d-765dc14df674'))
            ->setFullName('Mathieu MARCHOIS')
            ->setPassword('passwordHashed')
            ->setEmail('mathieu@fairness.coop')
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($date);

        $organization = (new Organization('d145a0e3-e397-412c-ba6a-90b150f7aec2'))
            ->setName('Fairness')
            ->setSiret('82050375300015');

        $organizationUser = (new OrganizationUser('f40f95eb-a7dd-4232-9f03-2db10f04f37f'))
            ->setOrganization($organization)
            ->setUser($user)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($organizationUser));

        $this->organizationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->isInstanceOf(Organization::class));

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23')
            ->willReturn($this->accessRequest);

        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                'd145a0e3-e397-412c-ba6a-90b150f7aec2',
                '0de5692b-cab1-494c-804d-765dc14df674',
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
            );

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($user));

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->equalTo($this->accessRequest));

        $handler = new ConvertAccessRequestToUserCommandHandler(
            $this->idFactory,
            $this->accessRequestRepository,
            $this->userRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
        );
        $command = new ConvertAccessRequestToUserCommand('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23');
        $handler($command);
    }

    public function testAccessRequestNotFound(): void
    {
        $this->expectException(AccessRequestNotFoundException::class);

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->organizationRepository
            ->expects(self::never())
            ->method('findOneBySiret');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationUserRepository
            ->expects(self::never())
            ->method('add');

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23')
            ->willReturn(null);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        $this->accessRequestRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new ConvertAccessRequestToUserCommandHandler(
            $this->idFactory,
            $this->accessRequestRepository,
            $this->userRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
        );
        $command = new ConvertAccessRequestToUserCommand('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23');
        $handler($command);
    }

    public function testUserAlreadyRegistered(): void
    {
        $this->expectException(UserAlreadyRegisteredException::class);

        $user = $this->createMock(User::class);
        $this->accessRequest
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('mathieu@fairness.coop');
        $this->accessRequest
            ->expects(self::once())
            ->method('getSiret')
            ->willReturn('aaaaaaaaaa');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn($user);

        $this->organizationRepository
            ->expects(self::never())
            ->method('findOneBySiret');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23')
            ->willReturn($this->accessRequest);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');
        $this->accessRequestRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new ConvertAccessRequestToUserCommandHandler(
            $this->idFactory,
            $this->accessRequestRepository,
            $this->userRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
        );
        $command = new ConvertAccessRequestToUserCommand('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23');
        $handler($command);
    }

    public function testSiretNotFound(): void
    {
        $this->expectException(SiretMissingException::class);

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->organizationRepository
            ->expects(self::never())
            ->method('findOneBySiret');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationUserRepository
            ->expects(self::never())
            ->method('add');

        $this->accessRequestRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23')
            ->willReturn($this->accessRequest);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');
        $this->accessRequestRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new ConvertAccessRequestToUserCommandHandler(
            $this->idFactory,
            $this->accessRequestRepository,
            $this->userRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
        );
        $command = new ConvertAccessRequestToUserCommand('e8a18fab-58d9-4aaf-bb47-b7a8edc20c23');
        $handler($command);
    }
}
