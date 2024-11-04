<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\UX\Turbo\TurboBundle;

final class DuplicateMeasureFragmentController extends AbstractRegulationController
{
    public function __construct(
        QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/{uuid}/duplicate',
        name: 'fragment_regulations_measure_duplicate',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid)
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        $measure = $this->queryBus->handle(new GetMeasureByUuidQuery($uuid));

        if (!$measure) {
            throw new NotFoundHttpException();
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    }
}
