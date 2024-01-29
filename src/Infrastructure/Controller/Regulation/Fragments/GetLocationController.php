<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\View\DetailLocationView;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Specification\CanDeleteLocations;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetLocationController extends AbstractRegulationController
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private CanDeleteLocations $canDeleteLocations,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/location/{uuid}',
        name: 'fragment_regulations_location',
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

        $location = $this->queryBus->handle(new GetLocationByUuidQuery($uuid));
        if (!$location) {
            throw new NotFoundHttpException();
        }

        if ($location->getRegulationOrder() !== $regulationOrderRecord->getRegulationOrder()) {
            throw new AccessDeniedHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_location.html.twig',
                context: [
                    'location' => DetailLocationView::fromEntity($location),
                    'generalInfo' => $generalInfo,
                    'canDelete' => $this->canDeleteLocations->isSatisfiedBy($regulationOrderRecord),
                ],
            ),
        );
    }
}
