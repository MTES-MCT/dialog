<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final class SaveRegulationOrderTemplateCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveRegulationOrderTemplateCommand $command): RegulationOrderTemplate
    {
        if ($regulationOrderTemplate = $command->regulationOrderTemplate) {
            $regulationOrderTemplate->update(
                name: $command->name,
                title: $command->title,
                visaContent: $command->visaContent,
                consideringContent: $command->consideringContent,
                articleContent: $command->articleContent,
            );

            return $regulationOrderTemplate;
        }

        return $this->regulationOrderTemplateRepository->add(
            (new RegulationOrderTemplate($this->idFactory->make()))
                ->setName($command->name)
                ->setTitle($command->title)
                ->setVisaContent($command->visaContent)
                ->setConsideringContent($command->consideringContent)
                ->setArticleContent($command->articleContent)
                ->setOrganization($command->organization)
                ->setCreatedAt($this->dateUtils->getNow()),
        );
    }
}
