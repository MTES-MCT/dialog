<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SavePasswordCommand;
use App\Infrastructure\Form\User\EditPasswordFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use App\Infrastructure\Security\User\PasswordUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
            /** @var PasswordUser */
            $abstractSymfonyUser = $this->authenticatedUser->getSessionUser();
            $abstractSymfonyUser->setPassword($user->getPasswordUser()->getPassword());

            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('profile.password.update.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_profile_password'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/profile/edit-password.html.twig',
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
