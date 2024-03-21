<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Query\GetAdministratorsQuery;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\Measure\MeasureFormType;
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

final class UpdateMeasureController extends AbstractRegulationController
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

        $form = $this->formFactory->create(
            type: MeasureFormType::class,
            data: $command,
            options: [
                'action' => $this->router->generate('fragment_regulations_measure_update', [
                    'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                    'uuid' => $uuid,
                ]),
                'administrators' => $administrators,
                'isPermanent' => $regulationOrder->isPermanent(),
            ],
        );
        $form->handleRequest($request);
        $commandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('fragment_regulations_measure', [
                        'uuid' => $uuid,
                        'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                    ]),
                    status: Response::HTTP_SEE_OTHER,
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
                    'measure' => MeasureView::fromEntity($measure),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $commandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
