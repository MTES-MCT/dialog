<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Profile;

use App\Infrastructure\Form\User\EditPasswordFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class EditPasswordController
{
    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
    ) {
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();
        $form = $this->formFactory->create(EditPasswordFormType::class);
        $form->handleRequest($request);

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
