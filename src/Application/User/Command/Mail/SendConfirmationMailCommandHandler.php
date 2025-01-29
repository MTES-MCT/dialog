<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\CommandBusInterface;
use App\Application\MailerInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\CreateTokenCommand;
use App\Domain\Mail;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\UserNotFoundException;

final readonly class SendConfirmationMailCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MailerInterface $mailer,
        private StringUtilsInterface $stringUtils,
    ) {
    }

    public function __invoke(SendConfirmationMailCommand $command): void
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        try {
            $token = $this->commandBus->handle(
                new CreateTokenCommand(
                    $email,
                    TokenTypeEnum::CONFIRM_ACCOUNT->value,
                ),
            );

            $this->mailer->send(
                new Mail(
                    address: $email,
                    subject: 'confirm_user_account.subject',
                    template: 'email/user/confirm_user_account.html.twig',
                    payload: [
                        'token' => $token->getToken(),
                        'fullName' => $token->getUser()->getFullName(),
                    ],
                ),
            );
        } catch (UserNotFoundException) {
            // Do nothing.
        }
    }
}
