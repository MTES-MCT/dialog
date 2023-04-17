<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\View\DetailLocationView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetLocationController extends AbstractRegulationController
{
    public function __construct(
        private readonly \Twig\Environment $twig,
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
            'regulationOrderRecordUuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
            'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        ],
    )]
    public function __invoke(string $regulationOrderRecordUuid, string $uuid): Response
    {
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
                    'regulationOrderRecord' => $regulationOrderRecord,
                ],
            ),
        );
    }
}
