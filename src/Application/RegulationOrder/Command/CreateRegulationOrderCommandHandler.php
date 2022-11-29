<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateRegulationOrderCommand $command): string
    {
        $uuid = $this->idFactory->make();
        $description = $command->description;
        $issuingAuthority = $command->issuingAuthority;

        $obj = $this->repository->save(
            new RegulationOrder(
                $uuid,
                $description,
                $issuingAuthority,
            ),
        );

        return $obj->getUuid();
    }
}
