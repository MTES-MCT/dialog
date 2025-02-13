<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\StartAbscissaOutOfRangeException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\GetRegulationOrderHistoryQuery;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

final class UpdateMeasureController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private CanUseRawGeoJSON $canUseRawGeoJSON,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/{uuid}/form',
        name: 'fragment_regulations_measure_update',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        $measure = $this->queryBus->handle(new GetMeasureByUuidQuery($uuid));
        if (!$measure) {
            throw new NotFoundHttpException();
        }

        $command = SaveMeasureCommand::create($regulationOrderRecord->getRegulationOrder(), $measure);
        $administrators = $this->queryBus->handle(new GetAdministratorsQuery());
        $storageAreas = $this->queryBus->handle($command->getStorageAreasQuery());
        $canUseRawGeoJSON = $this->canUseRawGeoJSON->isSatisfiedBy($this->security->getUser()?->getRoles());

        if ($canUseRawGeoJSON) {
            $command->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;
        }

        $form = $this->formFactory->create(
            type: MeasureFormType::class,
            data: $command,
            options: [
                'action' => $this->router->generate('fragment_regulations_measure_update', [
                    'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                    'uuid' => $uuid,
                ]),
                'administrators' => $administrators,
                'storage_areas' => $storageAreas,
                'isPermanent' => $regulationOrder->isPermanent(),
                'permissions' => $command->permissions,
            ],
        );
        $form->handleRequest($request);
        $commandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                $latestHistory = $this->queryBus->handle(new GetRegulationOrderHistoryQuery($regulationOrder->getUuid()));

                return new Response(
                    $this->twig->render(
                        name: 'regulation/fragments/_measure.updated.stream.html.twig',
                        context: [
                            'latestHistory' => $latestHistory,
                            'measureUuid' => $uuid,
                            'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
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
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_measure_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'measure' => MeasureView::fromEntity($measure),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $commandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
