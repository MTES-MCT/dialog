<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

final class Step5Controller
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/regulations/form/{uuid}/5',
        name: 'app_regulations_steps_5',
        requirements: ['uuid' => '.+'],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        try {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step5.html.twig',
                context: [
                    'stepNumber' => 5,
                    'uuid' => $uuid,
                    'regulationOrderRecord' => $regulationOrderRecord,
                ],
            ),
        );
    }
}
