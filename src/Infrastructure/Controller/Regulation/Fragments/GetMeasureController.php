<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetMeasureController extends AbstractRegulationController
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private CanDeleteMeasures $canDeleteMeasures,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/{uuid}',
        name: 'fragment_regulations_measure',
        methods: ['GET'],
        requirements: [
            'regulationOrderRecordUuid' => Requirement::UUID,
            'uuid' => Requirement::UUID,
        ],
    )]
    public function __invoke(string $regulationOrderRecordUuid, string $uuid): Response
    {
        /** @var GeneralInfoView */
        $generalInfo = $this->getRegulationOrderRecordUsing(function () use ($regulationOrderRecordUuid) {
            return $this->queryBus->handle(new GetGeneralInfoQuery($regulationOrderRecordUuid));
        });

        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        $measure = $this->queryBus->handle(new GetMeasureByUuidQuery($uuid));
        if (!$measure) {
            throw new NotFoundHttpException();
        }

        if ($measure->getRegulationOrder() !== $regulationOrderRecord->getRegulationOrder()) {
            throw new AccessDeniedHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_measure.html.twig',
                context: [
                    'measure' => MeasureView::fromEntity($measure),
                    'generalInfo' => $generalInfo,
                    'canDelete' => $this->canDeleteMeasures->isSatisfiedBy($regulationOrderRecord),
                ],
            ),
        );
    }
}
