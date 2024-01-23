<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInformationQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\View\GeneralInformationView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
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
        /** @var RegulationOrderRecordSummaryView */
        $regulationOrderRecord = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));
        });

        /** @var GeneralInformationView */
        $generalInformation = $this->queryBus->handle(new GetGeneralInformationQuery($uuid));

        return new Response(
            $this->twig->render(
                name: 'regulation/detail.html.twig',
                context: [
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'isDraft' => $generalInformation->isDraft(),
                    'canPublish' => $this->canRegulationOrderRecordBePublished->isSatisfiedBy($regulationOrderRecord),
                    'canDelete' => $this->canDeleteLocations->isSatisfiedBy($regulationOrderRecord),
                    'uuid' => $uuid,
                    'generalInformation' => $generalInformation,
                ],
            ),
        );
    }
}
