<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\User\OrganizationRegulationAccessInterface;

readonly class GeneralInfoView implements OrganizationRegulationAccessInterface
{
    public function __construct(
        public string $uuid,
        public string $identifier,
        public string $organizationName,
        public ?string $organizationLogo,
        public ?string $organizationUuid,
        public ?AddressView $organizationAddress,
        public string $status,
        public string $source,
        public string $regulationOrderUuid,
        public ?string $regulationOrderTemplateUuid,
        public string $category,
        public ?string $subject,
        public ?string $otherCategoryText,
        public string $title,
        public ?\DateTimeInterface $startDate,
        public ?\DateTimeInterface $endDate,
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

    public function allowsEditingContentInApplication(): bool
    {
        if ($this->isDraft()) {
            return true;
        }

        return $this->status === RegulationOrderRecordStatusEnum::PUBLISHED->value
            && $this->isEditableViaApplicationBySource();
    }

    public function isEditableViaApplicationBySource(): bool
    {
        return !\in_array(
            $this->source,
            [
                RegulationOrderRecordSourceEnum::LITTERALIS->value,
                RegulationOrderRecordSourceEnum::API->value,
            ],
            true,
        );
    }
}
