<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;

final class SaveFeedbackCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private FeedbackRepositoryInterface $feedbackRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveFeedbackCommand $command): void
    {
        $feedback = new Feedback(
            uuid: $this->idFactory->make(),
            content: $command->content,
            consentToBeContacted: $command->consentToBeContacted,
            user: $command->user,
        );
        $feedback->setCreatedAt($this->dateUtils->getNow());

        $this->feedbackRepository->add($feedback);
    }
}
