<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Application\Regulation\View\Measure\MeasureView;

/**
 * Vue d'un arrêté (regulation order) pour la liste exposée par l'API publique.
 *
 * Regroupe les informations générales de l'arrêté ainsi que ses mesures
 * (chaque mesure portant ses emprises et ses périodes d'application).
 */
final readonly class RegulationOrderForApiView
{
    /**
     * @param MeasureView[] $measures
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
        public ?string $organizationUuid,
        public string $organizationName,
        public array $measures,
    ) {
    }
}
