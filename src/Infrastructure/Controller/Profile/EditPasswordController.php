<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SavePasswordCommand;
use App\Infrastructure\Form\User\EditPasswordFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

final class EditPasswordController
{
    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private CommandBusInterface $commandBus,
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();
        $command = new SavePasswordCommand($user);
        $form = $this->formFactory->create(EditPasswordFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);
            $symfonyUser = $this->authenticatedUser->getSymfonyUser();
            $symfonyUser->setPassword($user->getPassword());

            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('profile.update.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_profile_password'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'profile/editPassword.html.twig',
                context: [
                    'user' => $user,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
