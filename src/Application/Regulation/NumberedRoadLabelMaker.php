<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Domain\Regulation\Location\NumberedRoad;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Construit le libellé lisible d'une route numérotée, identique à celui affiché sur le web
 * (ex. « D66 (Lozère) du PR 10+50 (côté U) au PR 13+0 (côté U) »), afin de garantir la
 * cohérence entre l'interface, l'export CSV et les flux CIFS/DATEX.
 */
final class NumberedRoadLabelMaker
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function make(NumberedRoad $numberedRoad): string
    {
        $roadNumber = $numberedRoad->getRoadNumber() ?? '';
        $administrator = $numberedRoad->getAdministrator();

        $label = $administrator ? trim(\sprintf('%s (%s)', $roadNumber, $administrator)) : trim($roadNumber);

        if ($referencePoints = $this->makeReferencePoints($numberedRoad)) {
            $label = trim($label . ' ' . $referencePoints);
        }

        return $label;
    }

    public function makeReferencePoints(NumberedRoad $numberedRoad): string
    {
        if (
            NumberedRoad::isPointNumberEmpty($numberedRoad->getFromPointNumber())
            || NumberedRoad::isPointNumberEmpty($numberedRoad->getToPointNumber())
        ) {
            return '';
        }

        return $this->translator->trans('regulation.location.reference_point', [
            '%fromPointNumber%' => $numberedRoad->getFromPointNumber(),
            '%fromAbscissa%' => $numberedRoad->getFromAbscissa() ?? 0,
            '%fromSide%' => $numberedRoad->getFromSide(),
            '%toPointNumber%' => $numberedRoad->getToPointNumber(),
            '%toAbscissa%' => $numberedRoad->getToAbscissa() ?? 0,
            '%toSide%' => $numberedRoad->getToSide(),
        ]);
    }
}
