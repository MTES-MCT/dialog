<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        protected QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{uuid}/general_info',
        name: 'fragment_regulations_general_info',
        requirements: ['uuid' => Requirement::UUID],
        methods: 'GET',
    )]
    public function __invoke(string $uuid): Response
    {
        /** @var GeneralInfoView */
        $generalInfo = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
        });

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_general_info.html.twig',
                context: [
                    'generalInfo' => $generalInfo,
                    'canEdit' => $generalInfo->isDraft(),
                ],
            ),
        );
    }
}
