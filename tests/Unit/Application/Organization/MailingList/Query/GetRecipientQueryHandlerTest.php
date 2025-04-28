<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\MailingList\Query;

use App\Application\Organization\MailingList\Query\GetRecipientQuery;
use App\Application\Organization\MailingList\Query\GetRecipientQueryHandler;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRecipientQueryHandlerTest extends TestCase
{
    public function testGetByUuid(): void
    {
        $mailingListUuid = 'dcab837f-4460-4355-99d5-bf4891c35f8f';
        $recipient = $this->createMock(MailingList::class);
        $mailingListRepository = $this->createMock(MailingListRepositoryInterface::class);

        $mailingListRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($mailingListUuid)
            ->willReturn($recipient);

        $handler = new GetRecipientQueryHandler($mailingListRepository);
        $mailingList = $handler(new GetRecipientQuery($mailingListUuid));

        $this->assertEquals($recipient, $mailingList);
    }
}
