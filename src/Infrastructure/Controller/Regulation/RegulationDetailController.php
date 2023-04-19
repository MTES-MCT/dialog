<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class RegulationDetailController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private readonly CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        private readonly CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_detail',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(string $uuid): Response
    {
        try {
            /** @var RegulationOrderRecordSummaryView */
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
                name: 'regulation/detail.html.twig',
                context: [
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'isDraft' => $regulationOrderRecord->isDraft(),
                    'canPublish' => $this->canRegulationOrderRecordBePublished->isSatisfiedBy($regulationOrderRecord),
                    'uuid' => $uuid,
                ],
            ),
        );
    }
}
