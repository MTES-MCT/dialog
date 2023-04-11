<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class DetailLocationView
{
    public function __construct(
        public readonly string $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
    ) {
    }

    // TODO: parse from $address

    public function getCity(): string
    {
        return 'La Madeleine';
    }

    public function getPostCode(): string
    {
        return '59110';
    }

    public function getRoadName(): string
    {
        return 'Rue de Flandre';
    }
}
