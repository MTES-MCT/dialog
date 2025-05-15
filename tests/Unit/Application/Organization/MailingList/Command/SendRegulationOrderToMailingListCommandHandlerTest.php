<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\MailingList\Command;

use App\Application\MailerInterface;
use App\Application\Organization\MailingList\Command\SendRegulationOrderToMailingListCommand;
use App\Application\Organization\MailingList\Command\SendRegulationOrderToMailingListCommandHandler;
use App\Domain\Mail;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\User\User;
use App\Infrastructure\Adapter\StringUtils;
use PHPUnit\Framework\TestCase;

final class SendRegulationOrderToMailingListCommandHandlerTest extends TestCase
{
    public function testSendEmails(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $user = $this->createMock(User::class);
        $stringUtils = $this->createMock(StringUtils::class);
        $mail = $this->createMock(MailerInterface::class);

        $handler = new SendRegulationOrderToMailingListCommandHandler($mail, $stringUtils);
        $command = $this->createMock(SendRegulationOrderToMailingListCommand::class);
        $command->emails = '   Mathieu@fairness.cooP  ';
        $command->recipients = [];
        $stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with($command->emails)
            ->willReturn('mathieu@fairness.coop');

        $command->regulationOrder = $regulationOrder;

        $user
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $command->user = $user;

        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        address: 'mathieu@fairness.coop',
                        subject: 'mailing_list.email.subject',
                        template: 'email/mailing_list/mailing_list_email.html.twig',
                        payload: [
                            'recipient' => [
                                'name' => null,
                                'email' => 'mathieu@fairness.coop',
                            ],
                            'regulationOrder' => $regulationOrder,
                            'userName' => 'Mathieu MARCHOIS',
                        ],
                    ),
                ),
            );

        ($handler)($command);
    }

    public function testSendEmailToMailingList(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $user = $this->createMock(User::class);
        $stringUtils = $this->createMock(StringUtils::class);
        $mail = $this->createMock(MailerInterface::class);

        $handler = new SendRegulationOrderToMailingListCommandHandler($mail, $stringUtils);
        $command = $this->createMock(SendRegulationOrderToMailingListCommand::class);
        $command->emails = '';
        $command->recipients = ['mathieu#mathieu@fairness.coop '];
        $recipient = explode('#', $command->recipients[0]);

        $stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with($recipient[1])
            ->willReturn('mathieu@fairness.coop');

        $command->regulationOrder = $regulationOrder;

        $user
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $command->user = $user;

        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        address: 'mathieu@fairness.coop',
                        subject: 'mailing_list.email.subject',
                        template: 'email/mailing_list/mailing_list_email.html.twig',
                        payload: [
                            'recipient' => [
                                'name' => 'mathieu',
                                'email' => 'mathieu@fairness.coop',
                            ],
                            'regulationOrder' => $regulationOrder,
                            'userName' => 'Mathieu MARCHOIS',
                        ],
                    ),
                ),
            );

        ($handler)($command);
    }
}
