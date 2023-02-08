<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\SymfonyUser;

final class SaveRegulationStep1Command implements CommandInterface
{
    public ?string $issuingAuthority;
    public ?string $description;

    public function __construct(
        public readonly SymfonyUser $user,
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        SymfonyUser $user,
        RegulationOrderRecord $regulationOrderRecord = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($user, $regulationOrderRecord);
        $command->issuingAuthority = $regulationOrder?->getIssuingAuthority()
            ?? $user->getOrganization()->getName();
        $command->description = $regulationOrder?->getDescription();

        return $command;
    }
}
