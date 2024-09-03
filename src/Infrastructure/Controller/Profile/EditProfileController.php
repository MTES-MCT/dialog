<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Profile;

use App\Application\CommandBusInterface;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
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

        $form = $this->formFactory->create(UserFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                //$this->commandBus->handle($command);

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
                name: 'organization/user/form.html.twig',
                context: [
                    'organization' => $organization,
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
