<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\View\DetailLocationView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\FeatureFlagService;
use App\Infrastructure\Form\Regulation\LocationFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

final class AddLocationController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private FeatureFlagService $featureFlagService,
        QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{uuid}/location/add',
        name: 'fragment_regulations_location_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $administrators = $this->queryBus->handle(new GetAdministratorsQuery());

        $command = SaveRegulationLocationCommand::create($regulationOrderRecord);

        $form = $this->formFactory->create(LocationFormType::class, $command, [
            'action' => $this->router->generate('fragment_regulations_location_add', ['uuid' => $uuid]),
            'administrators' => $administrators,
            'feature_road_type_enabled' => $this->featureFlagService->isFeatureEnabled('road_type_enabled', $request),
        ]);
        $form->handleRequest($request);
        $commandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $preExistingLocationUuids = $regulationOrderRecord->getLocationUuids();

                $location = $this->commandBus->handle($command);
                $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return new Response(
                    $this->twig->render(
                        name: 'regulation/fragments/_location.added.stream.html.twig',
                        context: [
                            'location' => DetailLocationView::fromEntity($location),
                            'regulationOrderRecordUuid' => $uuid,
                            'generalInfo' => $generalInfo,
                            'canDelete' => ($regulationOrderRecord->countLocations() + 1) > 1,
                            'preExistingLocationUuids' => $preExistingLocationUuids,
                        ],
                    ),
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
                    'location' => null,
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $commandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
