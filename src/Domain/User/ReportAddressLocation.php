<?php

declare(strict_types=1);

namespace App\Domain\User;

final class ReportAddressLocation implements \Stringable
{
    private function __construct(
        public readonly string $label,
    ) {
    }

    /**
     * Construit la localisation à partir des composants d'adresse.
     *
     * Cas 1 : Routes numérotées (administrator + roadNumber)
     * Cas 2 : Routes nommées (cityLabel + roadName)
     *
     * @return self|null null si aucun composant n'est fourni
     */
    public static function fromAddressParts(
        ?string $administrator = null,
        ?string $roadNumber = null,
        ?string $cityLabel = null,
        ?string $roadName = null,
    ): ?self {
        $parts = array_filter(
            [$administrator, $roadNumber, $cityLabel, $roadName],
            static fn (?string $part): bool => $part !== null && $part !== '',
        );

        if (empty($parts)) {
            return null;
        }

        return new self(implode(' - ', $parts));
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
