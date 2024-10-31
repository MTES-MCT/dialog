<?php

declare(strict_types=1);

namespace App\Application\Organization\VisaModel\Command;

use App\Application\CommandInterface;
use App\Domain\Organization\VisaModel\VisaModel;
use App\Domain\User\Organization;

final class SaveVisaModelCommand implements CommandInterface
{
    public ?string $name = null;
    public ?string $description = null;
    public array $visas = [];

    public function __construct(
        public readonly Organization $organization,
        public readonly ?VisaModel $visaModel = null,
    ) {
        $this->name = $visaModel?->getName();
        $this->description = $visaModel?->getDescription();
        $this->visas = $visaModel?->getVisas() ?? [];
    }
}
