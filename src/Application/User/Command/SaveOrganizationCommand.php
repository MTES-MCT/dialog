<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveOrganizationCommand implements CommandInterface
{
    public ?string $name;
    public ?string $address;
    public ?string $zipCode;
    public ?string $city;
    public ?string $addressComplement;
    public ?UploadedFile $file = null;

    public function __construct(
        public readonly Organization $organization,
    ) {
        $establishment = $organization->getEstablishment();

        $this->name = $organization->getName();
        $this->address = $establishment?->getAddress();
        $this->zipCode = $establishment?->getZipCode();
        $this->city = $establishment?->getCity();
        $this->addressComplement = $establishment?->getAddressComplement();
    }
}
