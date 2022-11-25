<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(private RegulationOrderRepositoryInterface $repository)
    {
    }

    public function __invoke(CreateRegulationOrderCommand $command)
    {
        $uuid = Uuid::v4()->__toString();
        $description = $command->description;
        $issuingAuthority = $command->issuingAuthority;

        $obj = new RegulationOrder($uuid, $description, $issuingAuthority);

        $this->repository->save($obj);
    }
}
