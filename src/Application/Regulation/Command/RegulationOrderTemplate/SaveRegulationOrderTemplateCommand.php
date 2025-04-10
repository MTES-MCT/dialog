<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\CommandInterface;
use App\Domain\Regulation\DefaultRegulationOrderTemplateContent;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\User\Organization;

final class SaveRegulationOrderTemplateCommand implements CommandInterface
{
    public ?string $title;
    public ?string $name;
    public ?string $visaContent;
    public ?string $consideringContent;
    public ?string $articleContent;

    public function __construct(
        public Organization $organization,
        public ?RegulationOrderTemplate $regulationOrderTemplate = null,
    ) {
        $this->name = $regulationOrderTemplate?->getName();
        $this->title = $regulationOrderTemplate?->getTitle() ?? DefaultRegulationOrderTemplateContent::DEFAULT_TITLE;
        $this->visaContent = $regulationOrderTemplate?->getVisaContent() ?? DefaultRegulationOrderTemplateContent::DEFAULT_VISA_CONTENT;
        $this->consideringContent = $regulationOrderTemplate?->getConsideringContent() ?? DefaultRegulationOrderTemplateContent::DEFAULT_CONSIDERING_CONTENT;
        $this->articleContent = $regulationOrderTemplate?->getArticleContent() ?? DefaultRegulationOrderTemplateContent::DEFAULT_ARTICLE_CONTENT;
    }
}
