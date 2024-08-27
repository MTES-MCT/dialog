<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

final class ListRegulationsDTO
{
    public ?string $identifier = null;
    public ?string $organization = null;
    public ?string $regulationOrderType = null;
    public ?string $status = null;
}
