<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

abstract class AbstractStepsController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    protected function getRegulationOrderRecord(string $uuid): RegulationOrderRecord
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        try {
            return $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }
    }
}
