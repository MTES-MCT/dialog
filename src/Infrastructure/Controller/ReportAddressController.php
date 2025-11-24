<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Infrastructure\Form\User\ReportAddressFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportAddressController
{
    public function __construct(
        private RouterInterface $router,
        private AuthenticatedUser $authenticatedUser,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/report-address',
        name: 'app_report_address',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();
        $command = new SaveReportAddressCommand($user);
        $form = $this->formFactory->create(ReportAddressFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);
            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('report_address.send.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_report_address'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/reportAddress.html.twig',
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
