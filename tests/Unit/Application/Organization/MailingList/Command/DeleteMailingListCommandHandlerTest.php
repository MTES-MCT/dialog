<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\MailingList\Command;

use App\Application\Organization\MailingList\Command\DeleteMailingListCommand;
use App\Application\Organization\MailingList\Command\DeleteMailingListCommandHandler;
use App\Domain\Organization\MailingList\Exception\MailingListNotFoundException;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteMailingListCommandHandlerTest extends TestCase
{
    private MockObject $mailingListRepository;

    public function setUp(): void
    {
        $this->mailingListRepository = $this->createMock(MailingListRepositoryInterface::class);
    }

    public function testRemove(): void
    {
        $mailingList = $this->createMock(MailingList::class);

        $this->mailingListRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($mailingList);

        $this->mailingListRepository
            ->expects(self::once())
            ->method('remove')
            ->with($mailingList);

        $handler = new DeleteMailingListCommandHandler(
            $this->mailingListRepository,
        );
        $command = new DeleteMailingListCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(MailingListNotFoundException::class);

        $this->mailingListRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn(null);

        $this->mailingListRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new DeleteMailingListCommandHandler(
            $this->mailingListRepository,
        );
        $command = new DeleteMailingListCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }
}
