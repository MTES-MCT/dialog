<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetInvitationByUuidQuery;
use App\Application\User\Query\GetInvitationByUuidQueryHandler;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Invitation;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetInvitationByUuidQueryHandlerTest extends TestCase
{
    public function testGetInvitation(): void
    {
        $invitation = $this->createMock(Invitation::class);

        $invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $invitationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($invitation);

        $handler = new GetInvitationByUuidQueryHandler($invitationRepository);
        $result = $handler(new GetInvitationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertSame($invitation, $result);
    }

    public function testInvitationNotFound(): void
    {
        $this->expectException(InvitationNotFoundException::class);

        $invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $invitationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetInvitationByUuidQueryHandler($invitationRepository);
        $handler(new GetInvitationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
