<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ConfirmAccountCommand;
use App\Application\User\Command\Mail\SendWelcomeEmailCommand;
use App\Domain\User\Exception\TokenExpiredException;
use App\Domain\User\Exception\TokenNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ConfirmAccountController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/register/{token}/confirm-account', name: 'app_register_confirm_account', methods: ['GET'])]
    public function __invoke(Request $request, string $token): Response
    {
        try {
            $email = $this->commandBus->handle(new ConfirmAccountCommand($token));
            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('register.succeeded.verified'));
            $this->commandBus->dispatchAsync(new SendWelcomeEmailCommand($email));

            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        } catch (TokenNotFoundException) {
            throw new NotFoundHttpException();
        } catch (TokenExpiredException) {
            throw new BadRequestHttpException('Token expired.');
        }
    }
}
