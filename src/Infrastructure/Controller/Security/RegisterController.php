<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\RegisterCommand;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Infrastructure\Form\User\RegisterFormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegisterController
{
    public function __construct(
        private RouterInterface $router,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
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
                $this->commandBus->handle($command);

                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $session->getFlashBag()->add('success', $this->translator->trans('register.succeeded'));

                return new RedirectResponse(
                    url: $this->router->generate('app_login'),
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
