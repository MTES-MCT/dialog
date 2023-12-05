<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SaveAccessRequestCommand;
use App\Domain\User\Exception\AccessAlreadyRequestedException;
use App\Infrastructure\Form\User\AccessRequestFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AccessRequestController
{
    public function __construct(
        private RouterInterface $router,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/access-request',
        name: 'app_access_request',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $command = new SaveAccessRequestCommand();
        $form = $this->formFactory->create(AccessRequestFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();

            try {
                $this->commandBus->handle($command);
            } catch (AccessAlreadyRequestedException) {
                $session->getFlashBag()->add('error', $this->translator->trans('accessRequest.send.error'));
            }

            return new RedirectResponse(
                url: $this->router->generate('app_access_request', ['success' => 1]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'accessRequest.html.twig',
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
