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

final readonly class SendForgotPasswordMailCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MailerInterface $mailer,
        private StringUtilsInterface $stringUtils,
    ) {
    }

    public function __invoke(SendForgotPasswordMailCommand $command): void
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        try {
            $token = $this->commandBus->handle(
                new CreateTokenCommand(
                    $email,
                    TokenTypeEnum::FORGOT_PASSWORD->value,
                ),
            );

            $this->mailer->send(
                new Mail(
                    address: $email,
                    subject: 'forgot_password.subject',
                    template: 'email/user/forgot_password.html.twig',
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
