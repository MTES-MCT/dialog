<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\Invitation\CreateAccountFromInvitationCommand;
use App\Application\User\Query\GetInvitationByUuidQuery;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Invitation;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Form\User\CreateAccountFromInvitationFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AcceptInvitationFromEmailController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private AuthenticatedUser $authenticatedUser,
        private UserRepositoryInterface $userRepository,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        '/invitations/{uuid}/accept',
        name: 'app_invitation_accept',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        try {
            $invitation = $this->queryBus->handle(new GetInvitationByUuidQuery($uuid));
        } catch (InvitationNotFoundException) {
            throw new NotFoundHttpException();
        }

        $user = $this->authenticatedUser->getUser();

        // User is logged in
        if ($user instanceof User) {
            return $this->handleAuthenticatedUser($request, $invitation, $user);
        }

        // User is not logged in
        $existingUser = $this->userRepository->findOneByEmail($invitation->getEmail());

        if ($existingUser instanceof User) {
            // User has an account but is not logged in
            return $this->handleExistingUserNotLoggedIn($request, $invitation);
        }

        // User doesn't have an account - show simplified registration form
        return $this->handleNewUser($request, $invitation);
    }

    private function handleAuthenticatedUser(Request $request, Invitation $invitation, User $user): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();

        if ($invitation->getEmail() !== $user->getEmail()) {
            $session->getFlashBag()->add('error', $this->translator->trans('invitation.accept.error_not_owned'));

            return new RedirectResponse(
                url: $this->urlGenerator->generate('app_regulations_list'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new RedirectResponse(
            url: $this->urlGenerator->generate('app_invitation_join', ['uuid' => $invitation->getUuid()]),
            status: Response::HTTP_SEE_OTHER,
        );
    }

    private function handleExistingUserNotLoggedIn(Request $request, Invitation $invitation): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();

        // Store the target path for after login
        $session->set(
            '_security.main.target_path',
            $this->urlGenerator->generate('app_invitation_accept', ['uuid' => $invitation->getUuid()]),
        );

        return new Response(
            content: $this->twig->render(
                name: 'security/accept_invitation_login.html.twig',
                context: [
                    'invitation' => $invitation,
                ],
            ),
        );
    }

    private function handleNewUser(Request $request, Invitation $invitation): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();

        // Store the target path for after ProConnect login
        $session->set(
            '_security.main.target_path',
            $this->urlGenerator->generate('app_invitation_accept', ['uuid' => $invitation->getUuid()]),
        );

        $command = new CreateAccountFromInvitationCommand($invitation->getUuid());
        $form = $this->formFactory->create(CreateAccountFromInvitationFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                $session->getFlashBag()->add('success', $this->translator->trans('invitation.accept.account_created'));
                $session->set('_security.main.target_path', $this->urlGenerator->generate('app_my_organizations'));

                return new RedirectResponse(
                    url: $this->urlGenerator->generate('app_login'),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (UserAlreadyRegisteredException) {
                // User was created between page load and form submission
                return $this->handleExistingUserNotLoggedIn($request, $invitation);
            }
        }

        return new Response(
            content: $this->twig->render(
                name: 'security/accept_invitation_register.html.twig',
                context: [
                    'invitation' => $invitation,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
