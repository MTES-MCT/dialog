<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
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
        $command = SaveMeasureCommand::create($regulationOrder);
        $administrators = $this->queryBus->handle(new GetAdministratorsQuery());

        $form = $this->formFactory->create(MeasureFormType::class, $command, [
            'action' => $this->router->generate('fragment_regulations_measure_add', ['uuid' => $uuid]),
            'administrators' => $administrators,
            'isPermanent' => $regulationOrder->isPermanent(),
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
                            'generalInfo' => $generalInfo,
                            'canDelete' => ($regulationOrderRecord->countMeasures() + 1) > 1,
                            'preExistingMeasureUuids' => $preExistingMeasureUuids,
                        ],
                    ),
                );
            } catch (GeocodingFailureException $exc) {
                $commandFailed = true;
                $form->get('locations')->get((string) $exc->getLocationIndex())->get('roadName')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators'),
                    ),
                );
            } catch (DepartmentalRoadGeocodingFailureException $exc) {
                $commandFailed = true;
                $form->get('locations')->get((string) $exc->getLocationIndex())->get('fromPointNumber')->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.departmental_road_geocoding_failed', [], 'validators'),
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
