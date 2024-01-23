<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\View\DetailLocationView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\LocationFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateLocationController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/location/{uuid}/form',
        name: 'fragment_regulations_location_update',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        $location = $this->queryBus->handle(new GetLocationByUuidQuery($uuid));
        if (!$location) {
            throw new NotFoundHttpException();
        }

        $command = SaveRegulationLocationCommand::create($regulationOrderRecord, $location);
        $administrators = $this->queryBus->handle(new GetAdministratorsQuery());

        $form = $this->formFactory->create(
            type: LocationFormType::class,
            data: $command,
            options: [
                'action' => $this->router->generate('fragment_regulations_location_update', [
                    'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                    'uuid' => $uuid,
                ]),
                'administrators' => $administrators,
            ],
        );
        $form->handleRequest($request);
        $commandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('fragment_regulations_location', [
                        'uuid' => $uuid,
                        'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                    ]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (GeocodingFailureException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators'),
                    ),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_location_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'location' => DetailLocationView::fromEntity($location),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $commandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
