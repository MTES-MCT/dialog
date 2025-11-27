<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Infrastructure\Form\User\ReportAddressFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

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
        '/regulations/{uuid}/report-address',
        name: 'app_report_address',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $user = $this->authenticatedUser->getUser();
        $command = new SaveReportAddressCommand($user);

        // Récupérer les paramètres de la requête
        $administrator = $request->query->get('administrator');
        $roadNumber = $request->query->get('roadNumber');
        $cityLabel = $request->query->get('cityLabel');
        $roadName = $request->query->get('roadName');

        // Construire la valeur de roadType
        $roadTypeParts = [];

        // Cas 1 : Routes numérotées (administrator + roadNumber)
        if ($administrator !== null && $administrator !== '') {
            $roadTypeParts[] = $administrator;
        }
        if ($roadNumber !== null && $roadNumber !== '') {
            $roadTypeParts[] = $roadNumber;
        }

        // Cas 2 : Routes nommées (cityLabel + roadName)
        if ($cityLabel !== null && $cityLabel !== '') {
            $roadTypeParts[] = $cityLabel;
        }
        if ($roadName !== null && $roadName !== '') {
            $roadTypeParts[] = $roadName;
        }

        if (!empty($roadTypeParts)) {
            $command->roadType = implode(' - ', $roadTypeParts);
        }

        $form = $this->formFactory->create(ReportAddressFormType::class, $command);
        $form->handleRequest($request);
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);
            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('report_address.send.success'));

            $redirectUrl = $this->router->generate('app_regulation_detail', ['uuid' => $uuid]);

            $frameId = $request->query->get('frameId', 'create-report-address-form-frame');

            return new Response(
                $this->twig->render(
                    'regulation/fragments/_reportAddress.stream.html.twig',
                    [
                        'redirectUrl' => $redirectUrl,
                        'frameId' => $frameId,
                    ],
                ),
                Response::HTTP_OK,
            );
        }

        $frameId = $request->query->get('frameId', 'create-report-address-form-frame');

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_reportAddress.stream.html.twig',
                context: [
                    'form' => $form->createView(),
                    'frameId' => $frameId,
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
