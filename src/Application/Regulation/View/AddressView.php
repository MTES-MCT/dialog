<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class AddressView
{
    public function __construct(
        public string $address,
        public string $zipCode,
        public string $city,
        public ?string $department,
        public ?string $addressComplement,
    ) {
    }
}
