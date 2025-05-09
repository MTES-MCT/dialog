<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\StartAbscissaOutOfRangeException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Location\GetStorageAreasByRoadNumbersQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\Measure\MeasureFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

final class AddMeasureController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private CanUseRawGeoJSON $canUseRawGeoJSON,

        QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{uuid}/measure/add',
        name: 'fragment_regulations_measure_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $organization = $regulationOrderRecord->getOrganization();
        $command = SaveMeasureCommand::create($regulationOrder);
        $administrators = $this->queryBus->handle(new GetAdministratorsQuery());
        $storageAreas = $this->queryBus->handle(new GetStorageAreasByRoadNumbersQuery()); // Show all because no road selected yet
        $canUseRawGeoJSON = $this->canUseRawGeoJSON->isSatisfiedBy($this->security->getUser()?->getRoles());

        if ($canUseRawGeoJSON) {
            $command->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;
        }

        $form = $this->formFactory->create(MeasureFormType::class, $command, [
            'action' => $this->router->generate('fragment_regulations_measure_add', ['uuid' => $uuid]),
            'administrators' => $administrators,
            'storage_areas' => $storageAreas,
            'isPermanent' => $regulationOrder->isPermanent(),
            'permissions' => $command->permissions,
            'organization' => $organization,
        ]);
        $form->handleRequest($request);
        $commandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $preExistingMeasureUuids = $regulationOrderRecord->getMeasureUuids();

                $measure = $this->commandBus->handle($command);
                $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return new Response(
                    $this->twig->render(
                        name: 'regulation/fragments/_measure.added.stream.html.twig',
                        context: [
                            'measure' => MeasureView::fromEntity($measure),
                            'regulationOrderRecordUuid' => $uuid,
                            'regulationOrderRecord' => $regulationOrderRecord,
                            'generalInfo' => $generalInfo,
                            'canDelete' => ($regulationOrderRecord->countMeasures() + 1) > 1,
                            'preExistingMeasureUuids' => $preExistingMeasureUuids,
                        ],
                    ),
                );
            } catch (LaneGeocodingFailureException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->get('locations')->get((string) $exc->getLocationIndex())->get('namedStreet')->get('fromPointType')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.lane_geocoding_failed', [], 'validators'),
                    ),
                );
            } catch (AbscissaOutOfRangeException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $field = $exc instanceof StartAbscissaOutOfRangeException ? 'fromAbscissa' : 'toAbscissa';
                $form->get('locations')->get((string) $exc->getLocationIndex())->get($exc->roadType)->get($field)->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.abscissa_out_of_range', [], 'validators'),
                    ),
                );
            } catch (RoadGeocodingFailureException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->get('locations')->get((string) $exc->getLocationIndex())->get($exc->roadType)->get('roadNumber')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.numbered_road_geocoding_failed', [], 'validators'),
                    ),
                );
            } catch (GeocodingFailureException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->get('locations')->get((string) $exc->getLocationIndex())->get('namedStreet')->get('roadName')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators'),
                    ),
                );
            } catch (OrganizationCannotInterveneOnGeometryException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->get('locations')->get((string) $exc->getLocationIndex())->get('roadType')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.organization_cannot_intervene_on_geometry', ['%organizationName%' => $organization->getName()], 'validators'),
                    ),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_measure_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'measure' => null,
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $commandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
