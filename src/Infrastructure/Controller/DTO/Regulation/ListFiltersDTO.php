<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO\Regulation;

use App\Domain\User\Organization;

final class ListFiltersDTO
{
    public bool $logged = false;
    public ?Organization $organization = null;
}
