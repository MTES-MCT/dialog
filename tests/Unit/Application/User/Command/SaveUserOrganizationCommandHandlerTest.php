<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\User\Command\SaveUserOrganizationCommand;
use App\Application\User\Command\SaveUserOrganizationCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SaveUserOrganizationCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $organizationUser = (new OrganizationUser('0de5692b-cab1-494c-804d-765dc14df674'))
            ->setUser($user)
            ->setOrganization($organization)
            ->setRoles([OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value]);

        $organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($organizationUser));

        $handler = new SaveUserOrganizationCommandHandler(
            $idFactory,
            $organizationUserRepository,
        );
        $command = new SaveUserOrganizationCommand($user, $organization);
        $command->roles = [OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value];

        $handler($command);
    }

    public function testUpdate(): void
    {
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::once())
            ->method('setRoles')
            ->with([OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value]);
        $organizationUser
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([OrganizationRolesEnum::ROLE_ORGA_ADMIN->value]);

        $organization = $this->createMock(Organization::class);
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $idFactory
            ->expects(self::never())
            ->method('make');

        $organizationUserRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveUserOrganizationCommandHandler(
            $idFactory,
            $organizationUserRepository,
        );
        $command = new SaveUserOrganizationCommand($user, $organization, $organizationUser);
        $command->roles = [OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value];

        $handler($command);
    }
}
