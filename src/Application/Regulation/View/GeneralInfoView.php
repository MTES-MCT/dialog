<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\User\OrganizationRegulationAccessInterface;

readonly class GeneralInfoView implements OrganizationRegulationAccessInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $organizationName,
        public readonly ?string $organizationLogo,
        public readonly ?string $organizationUuid,
        public readonly string $status,
        public readonly string $regulationOrderUuid,
        public readonly string $category,
        public readonly ?string $otherCategoryText,
        public readonly string $title,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
    ) {
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organizationUuid;
    }

    public function isDraft(): bool
    {
        return $this->status === RegulationOrderRecordStatusEnum::DRAFT->value;
    }
}
