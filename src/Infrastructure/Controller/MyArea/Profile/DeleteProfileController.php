<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\DeleteOrganizationUserCommand;
use App\Domain\User\Exception\UserCannotBeDeletedException;
use App\Infrastructure\Form\User\DeleteProfileFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteProfileController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private AuthenticatedUser $authenticatedUser,
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // $csrfToken = new CsrfToken('deleteUser',$request->request->get('token'));

        // if (!$this->csrfTokenManager->isTokenValid($csrfToken))
        // {
        //     throw new BadRequestException('Invalid CSRF token');
        // }

        $user = $this->authenticatedUser->getUser();
        // $command = new DeleteOrganizationUserCommand($user);
        $form = $this->formFactory->create(DeleteProfileFormType::class);
        $form->handleRequest($request);

        // try {
        //     $this->commandBus->handle($command);

        // } catch (UserCannotBeDeletedException) {
        //     throw new AccessDeniedHttpException;
        // }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/profile/delete-profile.html.twig',
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
