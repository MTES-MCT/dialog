<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\RegulationOrderForApiView;

/**
 * Représentation de réponse d'un arrêté pour l'API.
 *
 * Ce DTO d'Infrastructure constitue le contrat explicite exposé par l'API :
 * il n'expose que les champs voulus (contrairement aux vues applicatives qui
 * portent des données et getters internes). Sa structure est sérialisée
 * automatiquement par le Serializer de Symfony.
 */
final readonly class RegulationApiView
{
    /**
     * @param MeasureApiView[] $measures
     */
    public function __construct(
        public string $identifier,
        public string $status,
        public string $category,
        public ?string $subject,
        public ?string $otherCategoryText,
        public string $title,
        public ?\DateTimeInterface $startDate,
        public ?\DateTimeInterface $endDate,
        public OrganizationApiView $organization,
        public array $measures,
    ) {
    }

    /**
     * @param MeasureView[] $measures
     */
    public static function fromViews(GeneralInfoView $generalInfo, array $measures): self
    {
        return new self(
            identifier: $generalInfo->identifier,
            status: $generalInfo->status,
            category: $generalInfo->category,
            subject: $generalInfo->subject,
            otherCategoryText: $generalInfo->otherCategoryText,
            title: $generalInfo->title,
            startDate: $generalInfo->startDate,
            endDate: $generalInfo->endDate,
            organization: new OrganizationApiView(
                uuid: $generalInfo->organizationUuid,
                name: $generalInfo->organizationName,
            ),
            measures: array_map(MeasureApiView::fromView(...), $measures),
        );
    }

    public static function fromApiView(RegulationOrderForApiView $view): self
    {
        return new self(
            identifier: $view->identifier,
            status: $view->status,
            category: $view->category,
            subject: $view->subject,
            otherCategoryText: $view->otherCategoryText,
            title: $view->title,
            startDate: $view->startDate,
            endDate: $view->endDate,
            organization: new OrganizationApiView(
                uuid: $view->organizationUuid,
                name: $view->organizationName,
            ),
            measures: array_map(MeasureApiView::fromView(...), $view->measures),
        );
    }
}
