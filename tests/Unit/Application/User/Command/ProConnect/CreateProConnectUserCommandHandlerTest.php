<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\ProConnect;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\ProConnectUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class CreateProConnectUserCommandHandlerTest extends TestCase
{
    private CreateProConnectUserCommandHandler $handler;
    private IdFactoryInterface $idFactory;
    private UserRepositoryInterface $userRepository;
    private ProConnectUserRepositoryInterface $proConnectUserRepository;
    private OrganizationUserRepositoryInterface $organizationUserRepository;
    private OrganizationRepositoryInterface $organizationRepository;
    private DateUtilsInterface $dateUtils;
    private ApiOrganizationFetcherInterface $organizationFetcher;

    protected function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->proConnectUserRepository = $this->createMock(ProConnectUserRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->organizationFetcher = $this->createMock(ApiOrganizationFetcherInterface::class);

        $this->handler = new CreateProConnectUserCommandHandler(
            $this->idFactory,
            $this->userRepository,
            $this->proConnectUserRepository,
            $this->organizationUserRepository,
            $this->organizationRepository,
            $this->dateUtils,
            $this->organizationFetcher,
        );
    }

    public function testReturnsExistingUserWhenFound(): void
    {
        $email = 'mathieu@fairness.coop';
        $existingUser = $this->createMock(User::class);
        $command = new CreateProConnectUserCommand(
            $email,
            ['given_name' => 'Mathieu', 'family_name' => 'MARCHOIS', 'organization_siret' => '12345678901234'],
        );

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn($existingUser);

        $this->userRepository->expects(self::never())->method('add');
        $this->proConnectUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->organizationRepository->expects(self::never())->method('add');

        $this->organizationFetcher->expects(self::never())->method('findBySiret');

        $result = ($this->handler)($command);

        $this->assertSame($existingUser, $result);
    }

    public function testCreatesNewUserAndOrganizationWhenNotFound(): void
    {
        $now = new \DateTimeImmutable();
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $orgName = 'Test Organization';

        $command = new CreateProConnectUserCommand(
            $email,
            ['given_name' => 'Mathieu', 'family_name' => 'MARCHOIS', 'organization_siret' => $siret],
        );

        $userUuid = 'c74cbd34-0700-489e-833c-9775399bcf08';
        $orgUuid = 'c5612dd5-8fa8-489d-9a10-1647221ee6b2';
        $proConnectUuid = '601329e2-a369-4a50-ab56-f6999ed6116a';
        $orgUserUuid = 'e2ff59cc-c792-46b7-b7d8-9c6eaa3103e7';

        $this->idFactory
            ->expects(self::exactly(4))
            ->method('make')
            ->willReturnOnConsecutiveCalls($orgUuid, $userUuid, $proConnectUuid, $orgUserUuid);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($siret)
            ->willReturn(null);

        $this->organizationFetcher
            ->expects(self::once())
            ->method('findBySiret')
            ->with($siret)
            ->willReturn(['name' => $orgName]);

        // Création des objets attendus
        $expectedOrganization = (new Organization($orgUuid))
            ->setCreatedAt($now)
            ->setSiret($siret)
            ->setName($orgName);

        $expectedUser = (new User($userUuid))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail($email)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($now)
            ->setVerified();

        $expectedProConnectUser = new ProConnectUser(
            uuid: $proConnectUuid,
            user: $expectedUser,
        );

        $expectedUser->setProConnectUser($expectedProConnectUser);

        $expectedOrganizationUser = (new OrganizationUser($orgUserUuid))
            ->setUser($expectedUser)
            ->setOrganization($expectedOrganization)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $this->organizationRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedOrganization);

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedUser);

        $this->proConnectUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedProConnectUser);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedOrganizationUser);

        $result = ($this->handler)($command);

        $this->assertEquals($expectedUser, $result);
    }

    public function testThrowsExceptionWhenOrganizationNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $command = new CreateProConnectUserCommand(
            $email,
            ['given_name' => 'Mathieu', 'family_name' => 'MARCHOIS', 'organization_siret' => $siret],
        );

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($siret)
            ->willReturn(null);

        $this->organizationFetcher
            ->expects(self::once())
            ->method('findBySiret')
            ->with($siret)
            ->willThrowException(new OrganizationNotFoundException());

        $this->userRepository->expects(self::never())->method('add');
        $this->proConnectUserRepository->expects(self::never())->method('add');
        $this->organizationUserRepository->expects(self::never())->method('add');
        $this->organizationRepository->expects(self::never())->method('add');

        ($this->handler)($command);
    }

    public function testCreatesUserWithExistingOrganization(): void
    {
        $now = new \DateTimeImmutable();
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $existingOrg = new Organization('32516746-4fce-4750-ba83-7ae9b4290678');
        $existingOrg->setName('Existing Organization')->setSiret($siret);

        $command = new CreateProConnectUserCommand(
            $email,
            ['given_name' => 'Mathieu', 'family_name' => 'MARCHOIS', 'organization_siret' => $siret],
        );

        $userUuid = '87ab5863-a858-45d5-b4a1-e6af60d1aa28';
        $proConnectUuid = '4a3c557a-2be1-46f2-95e2-2dbd9ae091e0';
        $orgUserUuid = '140dc2e6-e5f6-4b1e-a47c-06c4f10c65d3';

        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturnOnConsecutiveCalls($userUuid, $proConnectUuid, $orgUserUuid);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($siret)
            ->willReturn($existingOrg);

        $this->organizationFetcher->expects(self::never())->method('findBySiret');
        $this->organizationRepository->expects(self::never())->method('add');

        $expectedUser = (new User($userUuid))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail($email)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($now)
            ->setVerified();

        $expectedProConnectUser = new ProConnectUser(
            uuid: $proConnectUuid,
            user: $expectedUser,
        );

        $expectedUser->setProConnectUser($expectedProConnectUser);

        $expectedOrganizationUser = (new OrganizationUser($orgUserUuid))
            ->setUser($expectedUser)
            ->setOrganization($existingOrg)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedUser);

        $this->proConnectUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedProConnectUser);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedOrganizationUser);

        $result = ($this->handler)($command);

        $this->assertEquals($expectedUser, $result);
    }
}
