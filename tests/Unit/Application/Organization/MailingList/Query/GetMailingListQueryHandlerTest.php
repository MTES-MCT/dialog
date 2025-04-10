<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\MailingList\Query;

use App\Application\Organization\MailingList\Query\GetMailingListQuery;
use App\Application\Organization\MailingList\Query\GetMailingListQueryHandler;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetMailingListQueryHandlerTest extends TestCase
{
    public function testGetByOrganization(): void
    {
        $rows = [
            [
                'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                'name' => 'Karine Marchand',
                'email' => 'email@mairie.gouv.fr',
                'role' => 'Mairie',
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'name' => 'Raymond Machin',
                'email' => 'email@transport.gouv.fr',
                'role' => 'Police municipale',
            ],
        ];

        $organizationUuid = 'dcab837f-4460-4355-99d5-bf4891c35f8f';

        $mailingListRepository = $this->createMock(MailingListRepositoryInterface::class);

        $mailingListRepository
            ->expects(self::once())
            ->method('findRecipientsByOrganizationUuid')
            ->with($organizationUuid)
            ->willReturn($rows);

        $handler = new GetMailingListQueryHandler($mailingListRepository);
        $mailingLists = $handler(new GetMailingListQuery($organizationUuid));

        $this->assertEquals($rows, $mailingLists);
    }
}
