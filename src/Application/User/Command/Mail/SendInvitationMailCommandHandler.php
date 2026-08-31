<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Domain\Mail;

final readonly class SendInvitationMailCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendInvitationMailCommand $command): void
    {
        $invitation = $command->invitation;
        $organizationName = $invitation->getOrganization()->getName();

        $this->mailer->send(
            new Mail(
                from: null,
                address: $invitation->getEmail(),
                subject: $invitation->isMandataire()
                    ? 'organization_invitation.mandataire.subject'
                    : 'organization_invitation.subject',
                template: 'email/user/organization_invitation.html.twig',
                payload: [
                    'fullName' => $invitation->getFullName(),
                    'invitedBy' => $invitation->getOwner()->getFullName(),
                    'organizationName' => $organizationName,
                    'invitationUuid' => $invitation->getUuid(),
                    'isMandataire' => $invitation->isMandataire(),
                ],
                subjectParams: [
                    '%organizationName%' => $organizationName,
                ],
            ),
        );
    }
}
