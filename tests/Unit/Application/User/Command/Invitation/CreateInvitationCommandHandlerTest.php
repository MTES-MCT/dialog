<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Invitation;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\Invitation\CreateInvitationCommand;
use App\Application\User\Command\Invitation\CreateInvitationCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Exception\InvitationAlreadyExistsException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\Repository\User\OrganizationUserRepository;
use PHPUnit\Framework\TestCase;

final class CreateInvitationCommandHandlerTest extends TestCase
{
    private InvitationRepositoryInterface $invitationRepository;
    private OrganizationUserRepository $organizationUserRepository;
    private IdFactoryInterface $idFactory;
    private DateUtilsInterface $dateUtils;
    private StringUtilsInterface $stringUtils;
    private CreateInvitationCommandHandler $handler;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepository::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);

        $this->handler = new CreateInvitationCommandHandler(
            $this->invitationRepository,
            $this->organizationUserRepository,
            $this->idFactory,
            $this->dateUtils,
            $this->stringUtils,
        );
    }

    public function testInvite(): void
    {
        $now = new \DateTimeImmutable('2025-02-12');
        $owner = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('327712a7-6573-43f0-843b-f0a368b3b45a');

        $command = new CreateInvitationCommand(
            organization: $organization,
            owner: $owner,
        );
        $command->email = ' Mathieu@fairness.cooP ';
        $command->role = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value;
        $command->fullName = 'Mathieu MARCHOIS';

        $this->stringUtils
            ->expects($this->once())
            ->method('normalizeEmail')
            ->with(' Mathieu@fairness.cooP ')
            ->willReturn('mathieu@fairness.coop');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByEmailAndOrganization')
            ->with('mathieu@fairness.coop', $organization)
            ->willReturn(null);

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findByEmailAndOrganization')
            ->with('mathieu@fairness.coop', '327712a7-6573-43f0-843b-f0a368b3b45a')
            ->willReturn(null);

        $this->idFactory
            ->expects($this->once())
            ->method('make')
            ->willReturn('80ab24ce-5297-46ed-b649-739bf47812fc');

        $this->dateUtils
            ->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $expectedInvitation = new Invitation(
            uuid: '80ab24ce-5297-46ed-b649-739bf47812fc',
            email: 'mathieu@fairness.coop',
            role: OrganizationRolesEnum::ROLE_ORGA_ADMIN->value,
            fullName: 'Mathieu MARCHOIS',
            createdAt: $now,
            owner: $owner,
            organization: $organization,
        );

        $this->invitationRepository
            ->expects($this->once())
            ->method('add')
            ->with($expectedInvitation)
            ->willReturn($expectedInvitation);

        $result = $this->handler->__invoke($command);
        $this->assertSame($expectedInvitation, $result);
    }

    public function testInvitationAlreadyExist(): void
    {
        $this->expectException(InvitationAlreadyExistsException::class);

        $organization = $this->createMock(Organization::class);
        $invitation = $this->createMock(Invitation::class);
        $owner = $this->createMock(User::class);
        $command = new CreateInvitationCommand(
            organization: $organization,
            owner: $owner,
        );
        $command->email = ' Mathieu@fairness.cooP ';
        $command->role = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value;
        $command->fullName = 'Mathieu MARCHOIS';

        $this->stringUtils
            ->expects($this->once())
            ->method('normalizeEmail')
            ->with(' Mathieu@fairness.cooP ')
            ->willReturn('mathieu@fairness.coop');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByEmailAndOrganization')
            ->with('mathieu@fairness.coop', $organization)
            ->willReturn($invitation);

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('findByEmailAndOrganization');

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->dateUtils
            ->expects($this->never())
            ->method('getNow');

        $this->invitationRepository
            ->expects($this->never())
            ->method('add');

        $this->handler->__invoke($command);
    }

    public function testUserAlreadyInOrganization(): void
    {
        $this->expectException(OrganizationUserAlreadyExistException::class);

        $organizationUser = $this->createMock(OrganizationUser::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('327712a7-6573-43f0-843b-f0a368b3b45a');

        $owner = $this->createMock(User::class);
        $command = new CreateInvitationCommand(
            organization: $organization,
            owner: $owner,
        );
        $command->email = ' Mathieu@fairness.cooP ';
        $command->role = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value;
        $command->fullName = 'Mathieu MARCHOIS';

        $this->stringUtils
            ->expects($this->once())
            ->method('normalizeEmail')
            ->with(' Mathieu@fairness.cooP ')
            ->willReturn('mathieu@fairness.coop');

        $this->invitationRepository
            ->expects($this->once())
            ->method('findOneByEmailAndOrganization')
            ->with('mathieu@fairness.coop', $organization)
            ->willReturn(null);

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findByEmailAndOrganization')
            ->with('mathieu@fairness.coop', '327712a7-6573-43f0-843b-f0a368b3b45a')
            ->willReturn($organizationUser);

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->dateUtils
            ->expects($this->never())
            ->method('getNow');

        $this->invitationRepository
            ->expects($this->never())
            ->method('add');

        $this->handler->__invoke($command);
    }
}
