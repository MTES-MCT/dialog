<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Exception\RegulationOrderTemplateNotFoundException;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final class DuplicateRegulationOrderTemplateCommandHandler
{
    public function __construct(
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateRegulationOrderTemplateCommand $command): void
    {
        $orginalRegulationOrderTemplate = $this->regulationOrderTemplateRepository->findOneByUuid($command->uuid);
        if (!$orginalRegulationOrderTemplate instanceof RegulationOrderTemplate) {
            throw new RegulationOrderTemplateNotFoundException();
        }

        $regulationOrderTemplateCommand = new SaveRegulationOrderTemplateCommand($command->organization);
        $regulationOrderTemplateCommand->name = \sprintf('%s (copie)', $orginalRegulationOrderTemplate->getName());
        $regulationOrderTemplateCommand->title = $orginalRegulationOrderTemplate->getTitle();
        $regulationOrderTemplateCommand->visaContent = $orginalRegulationOrderTemplate->getVisaContent();
        $regulationOrderTemplateCommand->consideringContent = $orginalRegulationOrderTemplate->getConsideringContent();
        $regulationOrderTemplateCommand->articleContent = $orginalRegulationOrderTemplate->getArticleContent();

        $this->commandBus->handle($regulationOrderTemplateCommand);
    }
}
