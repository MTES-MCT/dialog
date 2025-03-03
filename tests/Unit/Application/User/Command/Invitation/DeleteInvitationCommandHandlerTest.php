<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Invitation;

use App\Application\User\Command\Invitation\DeleteInvitationCommand;
use App\Application\User\Command\Invitation\DeleteInvitationCommandHandler;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Specification\CanUserEditOrganization;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use PHPUnit\Framework\TestCase;

class DeleteInvitationCommandHandlerTest extends TestCase
{
    private InvitationRepositoryInterface $invitationRepository;
    private CanUserEditOrganization $canUserEditOrganization;
    private DeleteInvitationCommandHandler $handler;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $this->canUserEditOrganization = $this->createMock(CanUserEditOrganization::class);

        $this->handler = new DeleteInvitationCommandHandler(
            $this->invitationRepository,
            $this->canUserEditOrganization,
        );
    }

    public function testDelete(): void
    {
        $user = $this->createMock(AbstractAuthenticatedUser::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('ca143c24-fa0f-4702-b0d4-876be006f8a7');

        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::exactly(2))
            ->method('getOrganization')
            ->willReturn($organization);

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn($invitation);

        $this->invitationRepository
            ->expects($this->once())
            ->method('delete')
            ->with($invitation);

        $this->canUserEditOrganization
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($organization, $user)
            ->willReturn(true);

        $command = new DeleteInvitationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->assertSame('ca143c24-fa0f-4702-b0d4-876be006f8a7', $this->handler->__invoke($command));
    }

    public function testInvitationNotOwned(): void
    {
        $this->expectException(InvitationNotOwnedException::class);

        $user = $this->createMock(AbstractAuthenticatedUser::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('ca143c24-fa0f-4702-b0d4-876be006f8a7');
        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::exactly(2))
            ->method('getOrganization')
            ->willReturn($organization);

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn($invitation);

        $this->invitationRepository
            ->expects($this->never())
            ->method('delete');

        $this->canUserEditOrganization
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($organization, $user)
            ->willReturn(false);

        $command = new DeleteInvitationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }

    public function testInvitationNotFound(): void
    {
        $this->expectException(InvitationNotFoundException::class);
        $user = $this->createMock(AbstractAuthenticatedUser::class);

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('12444823-fa32-477b-baf1-5a3cbcaf68a3')
            ->willReturn(null);

        $this->invitationRepository
            ->expects($this->never())
            ->method('delete');

        $this->canUserEditOrganization
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $command = new DeleteInvitationCommand('12444823-fa32-477b-baf1-5a3cbcaf68a3', $user);
        $this->handler->__invoke($command);
    }
}
