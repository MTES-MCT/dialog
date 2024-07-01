<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetOrganizationUuidByRegulationOrderRecordQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\ArrayRegulationMeasures;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class RegulationDetailController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        protected QueryBusInterface $queryBus,
        private CanDeleteMeasures $canDeleteMeasures,
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_detail',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        /** @var SymfonyUser|null */
        $currentUser = $this->security->getUser();

        /** @var GeneralInfoView */
        $generalInfo = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
        }, false);

        $organizationUuid = $this->queryBus->handle(new GetOrganizationUuidByRegulationOrderRecordQuery($uuid));
        $measures = $this->queryBus->handle(new GetMeasuresQuery($uuid));
        $isReadOnly = !($currentUser && $this->canOrganizationAccessToRegulation->isSatisfiedBy($organizationUuid, $currentUser->getOrganizationUuids()));

        $context = [
            'uuid' => $uuid,
            'isDraft' => $generalInfo->isDraft(),
            'canPublish' => !$isReadOnly && $this->canRegulationOrderRecordBePublished->isSatisfiedBy(new ArrayRegulationMeasures($measures)),
            'canDelete' => !$isReadOnly && $this->canDeleteMeasures->isSatisfiedBy(new ArrayRegulationMeasures($measures)),
            'isReadOnly' => $isReadOnly,
            'generalInfo' => $generalInfo,
            'measures' => $measures,
        ];

        return new Response(
            $this->twig->render(
                name: 'regulation/detail.html.twig',
                context: $context,
            ),
        );
    }
}
