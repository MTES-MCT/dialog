<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\MailingList\Command;

use App\Application\IdFactoryInterface;
use App\Application\Organization\MailingList\Command\SaveMailingListCommand;
use App\Application\Organization\MailingList\Command\SaveMailingListCommandHandler;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveMailingListCommandHandlerTest extends TestCase
{
    public function testAddRecipient(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $mailingListRepository = $this->createMock(MailingListRepositoryInterface::class);

        $organization = $this->createMock(Organization::class);
        $mailingList = new MailingList(
            uuid: 'c19f2baa-691e-4f6a-ac50-63e0ccd5af6a',
            name: 'Isabelle Truc',
            email: 'isabelle@beta.fr',
            role: 'Prefecture',
            organization: $organization,
        );

        $mailingListRepository
            ->expects(self::once())
            ->method('add')
            ->with($mailingList)
            ->willReturn($mailingList);

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('c19f2baa-691e-4f6a-ac50-63e0ccd5af6a');

        $handler = new SaveMailingListCommandHandler(
            $idFactory,
            $mailingListRepository,
        );
        $command = new SaveMailingListCommand($organization);
        $command->name = 'Isabelle Truc';
        $command->email = 'isabelle@beta.fr';
        $command->role = 'Prefecture';

        $this->assertEquals($mailingList, $handler($command));
    }

    public function testUpdate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $mailingListRepository = $this->createMock(MailingListRepositoryInterface::class);

        $organization = $this->createMock(Organization::class);
        $mailingList = $this->createMock(MailingList::class);

        $mailingList
            ->expects(self::once())
            ->method('update')
            ->with(
                'Isabelle Truc',
                'isabelle@beta.fr',
                'Prefecture',
            );

        $mailingListRepository
            ->expects(self::never())
            ->method('add');

        $idFactory
            ->expects(self::never())
            ->method('make');

        $handler = new SaveMailingListCommandHandler(
            $idFactory,
            $mailingListRepository,
        );
        $command = new SaveMailingListCommand($organization, $mailingList);
        $command->name = 'Isabelle Truc';
        $command->email = 'isabelle@beta.fr';
        $command->role = 'Prefecture';

        $this->assertEquals($mailingList, $handler($command));
    }
}
