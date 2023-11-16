<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

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
        $feedbackRepository = $this->createMock(FeedbackRepositoryInterface::class);
        $user = $this->createMock(User::class);

        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $feedback = new Feedback(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: $content,
            consentToBeContacted: $consentContact,
            user: $user,
        );

        $feedbackRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($feedback))
        ;

        $handler = new SaveFeedbackCommandHandler(
            $idFactory,
            $feedbackRepository,
        );
        $command = new SaveFeedbackCommand($user);
        $command->content = $content;
        $command->consentToBeContacted = $consentContact;
        $command->user = $user;

        $handler($command);
    }
}
