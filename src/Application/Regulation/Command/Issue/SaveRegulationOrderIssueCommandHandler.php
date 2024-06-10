<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Issue;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderIssue;
use App\Domain\Regulation\Repository\RegulationOrderIssueRepositoryInterface;

final class SaveRegulationOrderIssueCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderIssueRepositoryInterface $regulationOrderIssueRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveRegulationOrderIssueCommand $command): string
    {
        $regulationOrderIssue = $this->regulationOrderIssueRepository->add(
            new RegulationOrderIssue(
                uuid: $this->idFactory->make(),
                identifier: $command->identifier,
                organization: $command->organization,
                source: $command->source,
                level: $command->level,
                context: $command->context,
                geometry: $command->geometry,
                createdAt: $this->dateUtils->getNow(),
            ),
        );

        return $regulationOrderIssue->getUuid();
    }
}
