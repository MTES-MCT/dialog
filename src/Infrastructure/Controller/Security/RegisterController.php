<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\RegisterCommand;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Infrastructure\Form\User\RegisterFormType;
use App\Infrastructure\Security\Provider\UserProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegisterController
{
    public function __construct(
        private RouterInterface $router,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private Security $security,
        private UserProvider $userProvider,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $command = new RegisterCommand();
        $form = $this->formFactory->create(RegisterFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->commandBus->handle($command);
                $this->security->login($this->userProvider->loadUserByIdentifier($user->getEmail()));

                return new RedirectResponse(
                    url: $this->router->generate('app_regulations_list'),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (OrganizationNotFoundException) {
                $form->get('organizationSiret')->addError(
                    new FormError($this->translator->trans('register.error.organizationSiret_not_found')),
                );
            } catch (UserAlreadyRegisteredException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('register.error.already_exists')),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'register.html.twig',
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
