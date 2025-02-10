<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetInvitationsQuery;
use App\Application\User\Query\GetInvitationsQueryHandler;
use App\Application\User\View\InvitationView;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetInvitationsQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $invitation1 = new InvitationView(
            '42a1888f-29cb-4e32-a02f-49d278b6d128',
            'Mathieu MARCHOIS',
            'mathieu.marchois@beta.gouv.fr',
            OrganizationRolesEnum::ROLE_ORGA_ADMIN->value,
        );

        $invitation2 = new InvitationView(
            'd732584b-810f-4932-bd83-41b60c24c414',
            'mathieu.fernandez@beta.gouv.fr',
            'Mathieu FERNANDEZ',
            OrganizationRolesEnum::ROLE_ORGA_ADMIN->value,
        );

        $invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $invitationRepository
            ->expects(self::once())
            ->method('findByOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([$invitation1, $invitation2]);

        $handler = new GetInvitationsQueryHandler($invitationRepository);
        $result = $handler(new GetInvitationsQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $expectedResults = [$invitation1, $invitation2];

        $this->assertEquals($expectedResults, $result);
    }
}
