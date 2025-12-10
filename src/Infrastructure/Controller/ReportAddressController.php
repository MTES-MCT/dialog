<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Infrastructure\Form\User\ReportAddressFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        private QueryBusInterface $queryBus,
        private TranslatorInterface $translator,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/regulations/{uuid}/report-address',
        name: 'app_report_address',
        requirements: [
            'uuid' => Requirement::UUID,
        ],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request,
        string $uuid,
        #[MapQueryParameter] ?string $frameId = null,
        #[MapQueryParameter] ?string $administrator = null,
        #[MapQueryParameter] ?string $roadNumber = null,
        #[MapQueryParameter] ?string $cityLabel = null,
        #[MapQueryParameter] ?string $roadName = null,
        #[MapQueryParameter] ?string $roadBanId = null,
    ): Response {
        $user = $this->authenticatedUser->getUser();

        // Get organization from regulation order record
        $organizationUuid = null;
        try {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
            $organizationUuid = $regulationOrderRecord->getOrganizationUuid();
        } catch (\Exception $e) {
            // If regulation order record not found, continue without organization
        }

        $command = new SaveReportAddressCommand(
            $user,
            $administrator,
            $roadNumber,
            $cityLabel,
            $roadName,
            $roadBanId,
            $organizationUuid,
        );

        $frameId = $frameId ?? 'create-report-address-form-frame';

        $actionParams = array_merge(
            [
                'uuid' => $uuid,
            ],
            $request->query->all(),
        );

        $form = $this->formFactory->create(
            ReportAddressFormType::class,
            $command,
            [
                'action' => $this->router->generate('app_report_address', $actionParams),
            ],
        );
        $form->handleRequest($request);
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            $redirectUrl = $this->router->generate('app_regulation_detail', ['uuid' => $uuid]);

            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('report_address.send.success'));

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
