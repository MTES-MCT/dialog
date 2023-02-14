<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\RegulationOrder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

abstract class AbstractRegulationController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    protected function getRegulationOrder(string $uuid): RegulationOrder
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        try {
            return $this->queryBus->handle(new GetRegulationOrderByUuidQuery($uuid));
        } catch (RegulationOrderNotFoundException) {
            throw new NotFoundHttpException();
        }
    }
}
