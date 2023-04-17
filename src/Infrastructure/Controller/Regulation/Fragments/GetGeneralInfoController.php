<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetGeneralInfoController
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
        private readonly CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
    }

    #[Route(
        '/_fragment/regulations/{uuid}/general_info',
        name: 'fragment_regulations_general_info',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: 'GET',
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        // TODO: use a specific GetRegulationGeneralInfoQuery
        try {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        /** @var SymfonyUser */
        $user = $this->security->getUser();

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getOrganization())) {
            throw new AccessDeniedHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_general_info.html.twig',
                context: [
                    'regulationOrderRecord' => $regulationOrderRecord,
                ],
            ),
        );
    }
}
