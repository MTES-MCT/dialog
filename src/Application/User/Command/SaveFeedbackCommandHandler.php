<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;

final class SaveFeedbackCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private FeedbackRepositoryInterface $feedbackRepository,
    ) {
    }

    public function __invoke(SaveFeedbackCommand $command): void
    {
        $feedback = $this->feedbackRepository->add(
            new Feedback(
                uuid: $this->idFactory->make(),
                content: $command->content,
                consentToBeContacted: $command->consentToBeContacted,
                user: $command->user,
            ),
        );
    }
}
