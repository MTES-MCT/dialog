<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Location\GetRegulationLocationsQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\RegulationOrderLocationsView;
use App\Domain\Regulation\Specification\CanDeleteLocations;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class RegulationDetailController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        protected QueryBusInterface $queryBus,
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
        private CanDeleteLocations $canDeleteLocations,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_detail',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(string $uuid): Response
    {
        /** @var GeneralInfoView */
        $generalInfo = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
        });

        /** @var RegulationOrderLocationsView */
        $regulationOrderLocations = $this->queryBus->handle(new GetRegulationLocationsQuery($uuid));

        return new Response(
            $this->twig->render(
                name: 'regulation/detail.html.twig',
                context: [
                    'regulationOrderLocations' => $regulationOrderLocations,
                    'isDraft' => $generalInfo->isDraft(),
                    'canPublish' => $this->canRegulationOrderRecordBePublished->isSatisfiedBy($regulationOrderLocations),
                    'canDelete' => $this->canDeleteLocations->isSatisfiedBy($regulationOrderLocations),
                    'uuid' => $uuid,
                    'generalInfo' => $generalInfo,
                ],
            ),
        );
    }
}
