<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\EmailSendingException;
use App\Application\IdFactoryInterface;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;

final readonly class SaveFeedbackCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private FeedbackRepositoryInterface $feedbackRepository,
        private DateUtilsInterface $dateUtils,
        private MailerInterface $mailer,
        private string $emailSupport,
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
        $this->sendFeedbackByEmail($command);
    }

    private function sendFeedbackByEmail(SaveFeedbackCommand $command): void
    {
        try {
            $this->mailer->send(new Mail(
                address: $this->emailSupport,
                subject: 'contact.email.user_feedback_subject',
                template: 'email/user/user_feedback.html.twig',
                payload: [
                    'content' => $command->content,
                    'fullName' => $command->user->getFullName(),
                    'contactEmail' => $command->user->getEmail(),
                ],
            ));
        } catch (\Exception $e) {
            throw new EmailSendingException('Failed to send feedback by email : ' . $e->getMessage());
        }
    }
}
