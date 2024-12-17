<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\BothDirectionsNotSupportedAtPointNumbers;
use App\Application\PointNumberSideDetectorInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadSideEnum;
use App\Domain\Regulation\Location\NumberedRoad;

final class PointNumberSideDetector implements PointNumberSideDetectorInterface
{
    public function __construct(
        private readonly RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function detect(
        string $direction,
        string $administrator,
        string $roadNumber,
        string $fromPointNumber,
        int $fromAbscissa,
        string $toPointNumber,
        int $toAbscissa,
    ): array {
        $sidesAtFromPoint = $this->roadGeocoder->getAvailableSidesAtPointNumber(
            $administrator,
            $roadNumber,
            $fromPointNumber,
        );

        $sidesAtToPoint = $this->roadGeocoder->getAvailableSidesAtPointNumber(
            $administrator,
            $roadNumber,
            $toPointNumber,
        );

        $isSingleWayAtFromPoint = \in_array(RoadSideEnum::U->value, $sidesAtFromPoint);
        $isSingleWayAtToPoint = \in_array(RoadSideEnum::U->value, $sidesAtToPoint);

        if ($isSingleWayAtFromPoint || $isSingleWayAtToPoint) {
            // L'un des deux PR au moins est sur une chaussée unique
            // On doit forcément utiliser des PR de type U.
            return [RoadSideEnum::U->value, RoadSideEnum::U->value];
        }

        // Les deux PR se trouvent sur une section à chaussée séparée.
        // A priori, les deux côtés G ou D sont possibles au niveau de chaque PR.
        // On choisit le côté adéquat en fonction de la direction demandée et de l'ordre
        // des PR dans le sens des PR croissants.
        // Le "Double sens" n'est pas supporté pour l'instant, il faut saisir deux localisations,
        // une dans chaque sens.

        if ($direction === DirectionEnum::BOTH->value) {
            // TODO: handle in controller
            throw new BothDirectionsNotSupportedAtPointNumbers();
        }

        if (NumberedRoad::comparePointNumber($fromPointNumber, $fromAbscissa, $toPointNumber, $toAbscissa) <= 0) {
            // Le PR A est situé avant le PR B, dans l'ordre des PR croissants.
            // On doit choisir le côté D si le sens A -> B est demandé, et le côté G sinon.
            $side = $direction === DirectionEnum::A_TO_B->value
                ? RoadSideEnum::D->value
                : RoadSideEnum::G->value;

            return [$side, $side];
        }

        // Le PR A est situé après le PR B, donc on choisit le côté G si A->B est demandé, et le côté D sinon.
        $side = $direction === DirectionEnum::A_TO_B->value
            ? RoadSideEnum::G->value
            : RoadSideEnum::D->value;

        return [$side, $side];
    }
}
