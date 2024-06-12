<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Public;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class PublicRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/public/{uuid}',
        name: 'app_public_regulation',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        try {
            /** @var GeneralInfoView */
            $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        if ($generalInfo->isDraft()) {
            throw new NotFoundHttpException();
        }

        $measures = $this->queryBus->handle(new GetMeasuresQuery($uuid));

        return new Response(
            $this->twig->render(
                name: 'public/regulation.html.twig',
                context: [
                    'uuid' => $uuid,
                    'generalInfo' => $generalInfo,
                    'measures' => $measures,
                ],
            ),
        );
    }
}
