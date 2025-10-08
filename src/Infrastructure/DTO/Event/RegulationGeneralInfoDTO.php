<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

final class RegulationGeneralInfoDTO
{
    public ?string $identifier = null;
    public ?string $category = null;
    public ?string $subject = null;
    public ?string $otherCategoryText = null;
    public ?string $title = null;
}
