<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\User\Command\JoinOrganizationCommand;
use App\Application\User\Command\JoinOrganizationCommandHandler;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class JoinOrganizationCommandHandlerTest extends TestCase
{
    private InvitationRepositoryInterface $invitationRepository;
    private OrganizationUserRepositoryInterface $organizationUserRepository;
    private IdFactoryInterface $idFactory;
    private JoinOrganizationCommandHandler $handler;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);

        $this->handler = new JoinOrganizationCommandHandler(
            $this->invitationRepository,
            $this->organizationUserRepository,
            $this->idFactory,
        );
    }

    public function testSuccessfulJoin(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUuid')->willReturn('6c37dd7d-e78e-4de1-abf6-30ea3151287d');
        $user->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');

        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('93a77557-45f4-4b69-8001-fae52887b438');

        $invitation = $this->createMock(Invitation::class);
        $invitation->method('getOrganization')->willReturn($organization);
        $invitation->method('getRole')->willReturn('ROLE_USER');
        $invitation->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn($invitation);

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findOrganizationUser')
            ->with('93a77557-45f4-4b69-8001-fae52887b438', '6c37dd7d-e78e-4de1-abf6-30ea3151287d')
            ->willReturn(null);

        $this->idFactory
            ->expects($this->once())
            ->method('make')
            ->willReturn('7ceda9a3-b248-4bcc-908a-107badbc6353');

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('add')
            ->with(
                (new OrganizationUser('7ceda9a3-b248-4bcc-908a-107badbc6353'))
                    ->setOrganization($organization)
                    ->setUser($user)
                    ->setRoles('ROLE_USER'),
            );

        $this->invitationRepository
            ->expects($this->once())
            ->method('delete')
            ->with($invitation);

        $command = new JoinOrganizationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }

    public function testInvitationNotOwned(): void
    {
        $this->expectException(InvitationNotOwnedException::class);

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');

        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('93a77557-45f4-4b69-8001-fae52887b438');

        $invitation = $this->createMock(Invitation::class);
        $invitation->method('getOrganization')->willReturn($organization);
        $invitation->method('getRole')->willReturn('ROLE_USER');
        $invitation->method('getEmail')->willReturn('mathieu.marchois@gmail.com');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn($invitation);

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('findOrganizationUser');

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('add');

        $this->invitationRepository
            ->expects($this->never())
            ->method('delete')
            ->with($invitation);

        $command = new JoinOrganizationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }

    public function testUserOrganizationAlreadyExist(): void
    {
        $this->expectException(OrganizationUserAlreadyExistException::class);

        $userOrganization = $this->createMock(OrganizationUser::class);

        $user = $this->createMock(User::class);
        $user->method('getUuid')->willReturn('6c37dd7d-e78e-4de1-abf6-30ea3151287d');
        $user->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');

        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('93a77557-45f4-4b69-8001-fae52887b438');

        $invitation = $this->createMock(Invitation::class);
        $invitation->method('getOrganization')->willReturn($organization);
        $invitation->method('getRole')->willReturn('ROLE_USER');
        $invitation->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn($invitation);

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findOrganizationUser')
            ->with('93a77557-45f4-4b69-8001-fae52887b438', '6c37dd7d-e78e-4de1-abf6-30ea3151287d')
            ->willReturn($userOrganization);

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('add');

        $this->invitationRepository
            ->expects($this->never())
            ->method('delete');

        $command = new JoinOrganizationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }

    public function testInvitationNotFound(): void
    {
        $this->expectException(InvitationNotFoundException::class);

        $user = $this->createMock(User::class);

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn(null);

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('findOrganizationUser');

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('add');

        $this->invitationRepository
            ->expects($this->never())
            ->method('delete');

        $command = new JoinOrganizationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }
}
