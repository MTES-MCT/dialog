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

        $mail = $this->createMock(MailerInterface::class);
        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        address: 'mathieu@fairness.coop',
                        subject: 'organization_invitation.subject',
                        template: 'email/user/organization_invitation.html.twig',
                        payload: [
                            'fullName' => 'Mathieu MARCHOIS',
                            'invitedBy' => 'Mathieu FERNANDEZ',
                            'organizationName' => 'Dialog',
                            'invitationUuid' => 'b4bd1811-937b-43ed-b627-1a0f927311bd',
                        ],
                    ),
                ),
            );

        $handler = new SendInvitationMailCommandHandler($mail);
        $command = new SendInvitationMailCommand($invitation);
        ($handler)($command);
    }
}
