<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SaveProfileCommand;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Infrastructure\Form\User\ProfileFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EditProfileController
{
    public function __construct(
        private \Twig\Environment $twig,
        private CommandBusInterface $commandBus,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private AuthenticatedUser $authenticatedUser,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();
        $command = new SaveProfileCommand($user);
        $form = $this->formFactory->create(ProfileFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);
                $this->authenticatedUser->getSymfonyUser()->setfullName($user->getFullName());
                $this->authenticatedUser->getSymfonyUser()->setEmail($user->getEmail());

                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $session->getFlashBag()->add('success', $this->translator->trans('profile.update.success'));

                return new RedirectResponse(
                    url: $this->router->generate('app_profile'),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (EmailAlreadyExistsException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('email.already.exists', [], 'validators')),
                );
            }
        }

        return new Response(
            content: $this->twig->render(
                name: 'profile/index.html.twig',
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
