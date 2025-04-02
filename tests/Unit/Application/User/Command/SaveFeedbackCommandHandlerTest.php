<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
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

        $handler = new SaveFeedbackCommandHandler(
            $idFactory,
            $feedbackRepository,
            $dateUtils,
        );
        $command = new SaveFeedbackCommand($user);
        $command->content = $content;
        $command->consentToBeContacted = $consentContact;
        $command->user = $user;

        $handler($command);
    }
}
