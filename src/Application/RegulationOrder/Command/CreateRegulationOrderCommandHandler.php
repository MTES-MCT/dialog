<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(private RegulationOrderRepositoryInterface $repository)
    {
    }

    public function __invoke(CreateRegulationOrderCommand $command)
    {
        $this->repository->save(new RegulationOrder('4b2da330-1d33-4ac1-bd74-ecc09c63870f', $command->description, $command->issuingAuthority));
    }
}
