<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\Mail\SendForgotPasswordMailCommand;
use App\Infrastructure\Form\User\ForgotPasswordFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ForgotPasswordController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $command = new SendForgotPasswordMailCommand();
        $form = $this->formFactory->create(ForgotPasswordFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatchAsync($command);

            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('forgot_password.succeeded'));

            return new RedirectResponse($this->urlGenerator->generate('app_forgot_password'));
        }

        return new Response(
            content: $this->twig->render(
                name: 'forgot_password.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
