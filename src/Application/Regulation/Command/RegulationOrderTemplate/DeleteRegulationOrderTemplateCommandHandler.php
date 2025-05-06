<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Domain\Regulation\Exception\RegulationOrderTemplateCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderTemplateNotFoundException;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final class DeleteRegulationOrderTemplateCommandHandler
{
    public function __construct(
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
    ) {
    }

    public function __invoke(DeleteRegulationOrderTemplateCommand $command): void
    {
        $regulationOrderTemplate = $this->regulationOrderTemplateRepository->findOneByUuid($command->uuid);
        if (!$regulationOrderTemplate instanceof RegulationOrderTemplate) {
            throw new RegulationOrderTemplateNotFoundException();
        }

        if (!$regulationOrderTemplate->getOrganization()) {
            throw new RegulationOrderTemplateCannotBeDeletedException();
        }

        $this->regulationOrderTemplateRepository->remove($regulationOrderTemplate);
    }
}
