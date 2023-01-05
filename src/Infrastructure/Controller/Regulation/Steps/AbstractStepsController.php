<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractStepsController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function getRegulationOrderRecord(string $uuid): RegulationOrderRecord
    {
        try {
            return $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }
    }
}
