<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanRegulationOrderBePublished;

final class SaveRegulationStep5CommandHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private CanRegulationOrderBePublished $canRegulationOrderBePublished,
    ) {
    }

    public function __invoke(SaveRegulationStep5Command $command): void
    {
        $regulationOrder = $this->regulationOrderRepository
            ->findOneByUuid($command->regulationOrderUuid);

        if (!$regulationOrder instanceof RegulationOrder) {
            throw new RegulationOrderNotFoundException();
        }

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED &&
            false === $this->canRegulationOrderBePublished->isSatisfiedBy($regulationOrder)) {
            throw new RegulationOrderCannotBePublishedException();
        }

        $regulationOrder->getRegulationOrderRecord()->updateStatus($command->status);
    }
}
