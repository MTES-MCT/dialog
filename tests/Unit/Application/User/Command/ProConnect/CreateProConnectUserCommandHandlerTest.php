<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\ProConnect;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommandHandler;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\ProConnectUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\IsUserAlreadyRegisteredInOrganization;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class CreateProConnectUserCommandHandlerTest extends TestCase
{
    private CreateProConnectUserCommandHandler $handler;
    private IdFactoryInterface $idFactory;
    private UserRepositoryInterface $userRepository;
    private ProConnectUserRepositoryInterface $proConnectUserRepository;
    private OrganizationUserRepositoryInterface $organizationUserRepository;
    private IsUserAlreadyRegisteredInOrganization $isUserAlreadyRegisteredInOrganization;
    private DateUtilsInterface $dateUtils;
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->proConnectUserRepository = $this->createMock(ProConnectUserRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->isUserAlreadyRegisteredInOrganization = $this->createMock(IsUserAlreadyRegisteredInOrganization::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->handler = new CreateProConnectUserCommandHandler(
            $this->idFactory,
            $this->userRepository,
            $this->proConnectUserRepository,
            $this->organizationUserRepository,
            $this->isUserAlreadyRegisteredInOrganization,
            $this->dateUtils,
            $this->commandBus,
        );
    }

    public function testReturnEarlyWhenUserHasProConnectUser(): void
    {
        $email = 'mathieu@fairness.coop';
        $user = $this->createMock(User::class);
        $proConnectUser = $this->createMock(ProConnectUser::class);
        $command = new CreateProConnectUserCommand($email, 'Mathieu', 'MARCHOIS', '12345678901234');

        $user->expects($this->once())
            ->method('getProConnectUser')
            ->willReturn($proConnectUser);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn($user);

        $this->userRepository->expects($this->never())->method('add');
        $this->proConnectUserRepository->expects($this->never())->method('add');
        $this->organizationUserRepository->expects($this->never())->method('add');
        $this->commandBus->expects($this->never())->method('handle');

        ($this->handler)($command);
    }

    public function testCreateWithExistingUserAlreadyInOrganization(): void
    {
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $proConnectUserUuid = 'e2ff59cc-c792-46b7-b7d8-9c6eaa3103e7';
        $command = new CreateProConnectUserCommand($email, 'Mathieu', 'MARCHOIS', $siret);

        $organizationView = new GetOrCreateOrganizationView($organization, false);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn($user);

        // User doesn't have a ProConnectUser yet
        $user->expects($this->once())
            ->method('getProConnectUser')
            ->willReturn(null);

        $this->commandBus
            ->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(GetOrCreateOrganizationBySiretCommand::class))
            ->willReturn($organizationView);

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->isUserAlreadyRegisteredInOrganization
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($email, $organization)
            ->willReturn(true);

        $this->idFactory
            ->expects($this->once())
            ->method('make')
            ->willReturn($proConnectUserUuid);

        $user->expects($this->once())
            ->method('setProConnectUser')
            ->with($this->isInstanceOf(ProConnectUser::class));

        $this->proConnectUserRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(ProConnectUser::class));

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('add');

        ($this->handler)($command);
    }

    public function testCreateWithExistingUserNotInOrganization(): void
    {
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $orgUserUuid = 'c74cbd34-0700-489e-833c-9775399bcf08';
        $proConnectUserUuid = 'e2ff59cc-c792-46b7-b7d8-9c6eaa3103e7';

        $command = new CreateProConnectUserCommand($email, 'Mathieu', 'MARCHOIS', $siret);

        $organizationView = new GetOrCreateOrganizationView($organization, false);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn($user);

        // User doesn't have a ProConnectUser yet
        $user->expects($this->once())
            ->method('getProConnectUser')
            ->willReturn(null);

        $this->commandBus
            ->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(GetOrCreateOrganizationBySiretCommand::class))
            ->willReturn($organizationView);

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->isUserAlreadyRegisteredInOrganization
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($email, $organization)
            ->willReturn(false);

        $this->idFactory
            ->expects($this->exactly(2))
            ->method('make')
            ->willReturnOnConsecutiveCalls($orgUserUuid, $proConnectUserUuid);

        $user->expects($this->once())
            ->method('setProConnectUser')
            ->with($this->isInstanceOf(ProConnectUser::class));

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(OrganizationUser::class));

        $this->proConnectUserRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(ProConnectUser::class));

        ($this->handler)($command);
    }

    public function testCreateWithNewUser(): void
    {
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';
        $now = new \DateTimeImmutable();
        $userUuid = 'c74cbd34-0700-489e-833c-9775399bcf08';
        $orgUserUuid = '601329e2-a369-4a50-ab56-f6999ed6116a';
        $proConnectUserUuid = 'e2ff59cc-c792-46b7-b7d8-9c6eaa3103e7';
        $organization = $this->createMock(Organization::class);

        $command = new CreateProConnectUserCommand($email, 'Mathieu', 'MARCHOIS', $siret);

        $organizationView = new GetOrCreateOrganizationView($organization, true);

        // Simuler qu'aucun utilisateur n'existe
        $this->userRepository
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn(null);

        $this->dateUtils
            ->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $this->idFactory
            ->expects($this->exactly(3))
            ->method('make')
            ->willReturnOnConsecutiveCalls($userUuid, $orgUserUuid, $proConnectUserUuid);

        // Capturer l'utilisateur créé pour les vérifications suivantes
        $createdUser = null;
        $this->userRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (User $user) use ($email, $userUuid, &$createdUser) {
                $createdUser = $user;

                return $user->getUuid() === $userUuid && $user->getEmail() === $email;
            }));

        $this->commandBus
            ->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(GetOrCreateOrganizationBySiretCommand::class))
            ->willReturn($organizationView);

        $this->isUserAlreadyRegisteredInOrganization
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($email, $organization)
            ->willReturn(false);

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (OrganizationUser $orgUser) use (&$createdUser) {
                return $orgUser->getUser() === $createdUser;
            }));

        $this->proConnectUserRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ProConnectUser $proConnectUser) use (&$createdUser) {
                return $proConnectUser->getUser() === $createdUser;
            }));

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenOrganizationNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $user = $this->createMock(User::class);
        $email = 'mathieu@fairness.coop';
        $siret = '12345678901234';

        $command = new CreateProConnectUserCommand($email, 'Mathieu', 'MARCHOIS', $siret);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($email)
            ->willReturn($user);

        $this->commandBus
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new OrganizationNotFoundException());

        $this->userRepository->expects($this->never())->method('add');
        $this->proConnectUserRepository->expects($this->never())->method('add');
        $this->organizationUserRepository->expects($this->never())->method('add');

        ($this->handler)($command);
    }
}
