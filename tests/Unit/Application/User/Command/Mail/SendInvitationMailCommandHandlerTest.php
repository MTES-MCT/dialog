<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Application\User\Command\Mail\SendInvitationMailCommand;
use App\Application\User\Command\Mail\SendInvitationMailCommandHandler;
use App\Domain\Mail;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SendInvitationMailCommandHandlerTest extends TestCase
{
    public function testSendInvitation(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getFullName')->willReturn('Mathieu FERNANDEZ');

        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Dialog');

        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::once())
            ->method('getOwner')
            ->willReturn($user);
        $invitation
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('mathieu@fairness.coop');
        $invitation
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('b4bd1811-937b-43ed-b627-1a0f927311bd');
        $invitation
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $invitation
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $invitation
            ->expects(self::exactly(2))
            ->method('isMandataire')
            ->willReturn(false);

        $mail = $this->createMock(MailerInterface::class);
        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        from: null,
                        address: 'mathieu@fairness.coop',
                        subject: 'organization_invitation.subject',
                        template: 'email/user/organization_invitation.html.twig',
                        payload: [
                            'fullName' => 'Mathieu MARCHOIS',
                            'invitedBy' => 'Mathieu FERNANDEZ',
                            'organizationName' => 'Dialog',
                            'invitationUuid' => 'b4bd1811-937b-43ed-b627-1a0f927311bd',
                            'isMandataire' => false,
                        ],
                        subjectParams: [
                            '%organizationName%' => 'Dialog',
                        ],
                    ),
                ),
            );

        $handler = new SendInvitationMailCommandHandler($mail);
        $command = new SendInvitationMailCommand($invitation);
        ($handler)($command);
    }

    public function testSendMandataireInvitation(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getFullName')->willReturn('Mathieu FERNANDEZ');

        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Dialog');

        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::once())
            ->method('getOwner')
            ->willReturn($user);
        $invitation
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('marc.prestataire@example.com');
        $invitation
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('b4bd1811-937b-43ed-b627-1a0f927311bd');
        $invitation
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Marc PRESTATAIRE');
        $invitation
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $invitation
            ->expects(self::exactly(2))
            ->method('isMandataire')
            ->willReturn(true);

        $mail = $this->createMock(MailerInterface::class);
        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        from: null,
                        address: 'marc.prestataire@example.com',
                        subject: 'organization_invitation.mandataire.subject',
                        template: 'email/user/organization_invitation.html.twig',
                        payload: [
                            'fullName' => 'Marc PRESTATAIRE',
                            'invitedBy' => 'Mathieu FERNANDEZ',
                            'organizationName' => 'Dialog',
                            'invitationUuid' => 'b4bd1811-937b-43ed-b627-1a0f927311bd',
                            'isMandataire' => true,
                        ],
                        subjectParams: [
                            '%organizationName%' => 'Dialog',
                        ],
                    ),
                ),
            );

        $handler = new SendInvitationMailCommandHandler($mail);
        $command = new SendInvitationMailCommand($invitation);
        ($handler)($command);
    }
}
