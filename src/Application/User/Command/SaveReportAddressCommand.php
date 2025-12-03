<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class SaveReportAddressCommand implements CommandInterface
{
    public ?string $content = null;
    public ?string $location = null;

    public function __construct(
        public User $user,
        ?string $administrator = null,
        ?string $roadNumber = null,
        ?string $cityLabel = null,
        ?string $roadName = null,
    ) {
        $this->location = $this->buildLocation($administrator, $roadNumber, $cityLabel, $roadName);
    }

    /**
     * Construit la valeur de location à partir des paramètres fournis.
     *
     * Cas 1 : Routes numérotées (administrator + roadNumber)
     * Cas 2 : Routes nommées (cityLabel + roadName)
     *
     * @return string|null La valeur construite ou null si aucun paramètre n'est fourni
     */
    public function buildLocation(
        ?string $administrator = null,
        ?string $roadNumber = null,
        ?string $cityLabel = null,
        ?string $roadName = null,
    ): ?string {
        $locationParts = [];

        // Cas 1 : Routes numérotées (administrator + roadNumber)
        if ($administrator !== null && $administrator !== '') {
            $locationParts[] = $administrator;
        }
        if ($roadNumber !== null && $roadNumber !== '') {
            $locationParts[] = $roadNumber;
        }

        // Cas 2 : Routes nommées (cityLabel + roadName)
        if ($cityLabel !== null && $cityLabel !== '') {
            $locationParts[] = $cityLabel;
        }
        if ($roadName !== null && $roadName !== '') {
            $locationParts[] = $roadName;
        }

        if (empty($locationParts)) {
            return null;
        }

        return implode(' - ', $locationParts);
    }
}
