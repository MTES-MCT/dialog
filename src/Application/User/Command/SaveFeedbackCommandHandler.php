<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class SaveFeedbackCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private FeedbackRepositoryInterface $feedbackRepository,
        private DateUtilsInterface $dateUtils,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
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
            $email = new Mail(
                address: $this->emailSupport,
                subject: 'contact.email.user_report_subject',
                template: 'email/user/user_report.html.twig',
                payload: [
                    'content' => $command->content,
                    'fullName' => $command->user->getFullName(),
                    'email' => $command->user->getEmail(),
                ],
            );

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send feedback by email', [
                'userId' => $command->user->getUuid(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
