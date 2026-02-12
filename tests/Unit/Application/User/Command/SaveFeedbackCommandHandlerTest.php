<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\EmailSendingException;
use App\Application\IdFactoryInterface;
use App\Application\MailerInterface;
use App\Application\User\Command\SaveFeedbackCommand;
use App\Application\User\Command\SaveFeedbackCommandHandler;
use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SaveFeedbackCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $content = 'ceci est un avis';
        $consentContact = false;
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $feedbackRepository = $this->createMock(FeedbackRepositoryInterface::class);
        $user = $this->createMock(User::class);
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $feedback = new Feedback(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: $content,
            consentToBeContacted: $consentContact,
            user: $user,
        );
        $feedback->setCreatedAt($date);

        $feedbackRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($feedback));

        $mailer = $this->createMock(MailerInterface::class);
        $emailSupport = 'support@example.com';

        $handler = new SaveFeedbackCommandHandler(
            $idFactory,
            $feedbackRepository,
            $dateUtils,
            $mailer,
            $emailSupport,
        );
        $command = new SaveFeedbackCommand($user);
        $command->content = $content;
        $command->consentToBeContacted = $consentContact;
        $command->user = $user;

        $handler($command);
    }

    public function testCreateWithMailerException(): void
    {
        $content = 'ceci est un avis';
        $consentContact = false;
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $feedbackRepository = $this->createMock(FeedbackRepositoryInterface::class);
        $user = $this->createMock(User::class);
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $feedback = new Feedback(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: $content,
            consentToBeContacted: $consentContact,
            user: $user,
        );
        $feedback->setCreatedAt($date);

        $feedbackRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($feedback));

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('Email service unavailable'));

        $emailSupport = 'support@example.com';

        $handler = new SaveFeedbackCommandHandler(
            $idFactory,
            $feedbackRepository,
            $dateUtils,
            $mailer,
            $emailSupport,
        );
        $command = new SaveFeedbackCommand($user);
        $command->content = $content;
        $command->consentToBeContacted = $consentContact;
        $command->user = $user;

        $this->expectException(EmailSendingException::class);
        $this->expectExceptionMessage('Failed to send feedback by email : Email service unavailable');

        $handler($command);
    }
}
