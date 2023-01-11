<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep3Command implements CommandInterface
{
    public ?\DateTimeInterface $startPeriod;
    public ?\DateTimeInterface $endPeriod = null;

    public function __construct(
        public readonly RegulationOrderRecord $regulationOrderRecord,
        public readonly ?OverallPeriod $overallPeriod = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord,
        OverallPeriod $overallPeriod = null,
    ): self {
        $command = new self($regulationOrderRecord, $overallPeriod);
        $command->startPeriod = $overallPeriod?->getStartPeriod();
        $command->endPeriod = $overallPeriod?->getEndPeriod();

        return $command;
    }
}
