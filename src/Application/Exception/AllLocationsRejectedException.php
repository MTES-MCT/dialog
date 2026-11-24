<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Application\Regulation\View\RejectedLocationView;

/**
 * Aucune des localisations envoyées pour l'arrêté n'a pu être intégrée : l'arrêté n'est pas enregistré.
 */
final class AllLocationsRejectedException extends \Exception
{
    /**
     * @param RejectedLocationView[] $rejectedLocations
     */
    public function __construct(
        private readonly array $rejectedLocations,
    ) {
        parent::__construct('All locations were rejected');
    }

    /** @return RejectedLocationView[] */
    public function getRejectedLocations(): array
    {
        return $this->rejectedLocations;
    }
}
