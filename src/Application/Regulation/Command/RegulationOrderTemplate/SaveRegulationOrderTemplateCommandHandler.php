<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\DateUtilsInterface;
use App\Application\HtmlSanitizerInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;

final class SaveRegulationOrderTemplateCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository,
        private DateUtilsInterface $dateUtils,
        private HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public function __invoke(SaveRegulationOrderTemplateCommand $command): RegulationOrderTemplate
    {
        // Le contenu provient d'un éditeur riche : on le nettoie côté serveur
        // pour empêcher le stockage de HTML malveillant (XSS stockée).
        $title = $this->htmlSanitizer->sanitize($command->title);
        $visaContent = $this->htmlSanitizer->sanitize($command->visaContent);
        $consideringContent = $this->htmlSanitizer->sanitize($command->consideringContent);
        $articleContent = $this->htmlSanitizer->sanitize($command->articleContent);

        if ($regulationOrderTemplate = $command->regulationOrderTemplate) {
            $regulationOrderTemplate->update(
                name: $command->name,
                title: $title,
                visaContent: $visaContent,
                consideringContent: $consideringContent,
                articleContent: $articleContent,
            );

            return $regulationOrderTemplate;
        }

        return $this->regulationOrderTemplateRepository->add(
            (new RegulationOrderTemplate($this->idFactory->make()))
                ->setName($command->name)
                ->setTitle($title)
                ->setVisaContent($visaContent)
                ->setConsideringContent($consideringContent)
                ->setArticleContent($articleContent)
                ->setOrganization($command->organization)
                ->setCreatedAt($this->dateUtils->getNow()),
        );
    }
}
