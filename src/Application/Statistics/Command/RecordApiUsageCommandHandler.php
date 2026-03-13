<?php

declare(strict_types=1);

namespace App\Application\Statistics\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Statistics\ApiUsageDaily;
use App\Domain\Statistics\Repository\ApiUsageDailyRepositoryInterface;

final class RecordApiUsageCommandHandler
{
    public function __construct(
        private readonly ApiUsageDailyRepositoryInterface $apiUsageDailyRepository,
        private readonly DateUtilsInterface $dateUtils,
        private readonly IdFactoryInterface $idFactory,
    ) {
    }

    public function __invoke(RecordApiUsageCommand $command): void
    {
        $day = $this->dateUtils->getNow()->setTime(0, 0, 0);
        $existing = $this->apiUsageDailyRepository->findOneByDayAndType($day, $command->type);

        if ($existing) {
            $existing->setCount($existing->getCount() + 1);
        } else {
            $this->apiUsageDailyRepository->add(new ApiUsageDaily(
                uuid: $this->idFactory->make(),
                day: $day,
                type: $command->type,
                count: 1,
            ));
        }
    }
}
