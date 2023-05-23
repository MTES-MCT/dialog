<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations\Gacs\Models;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Infrastructure\Integrations\Gacs\Exception\SkipException;

final class GacsMesure
{
    public const TAB_ID = 1200;
    public const NOM = 1202;

    private const CIRCULATION_ALTERNEE = 102;
    private const CIRCULATION_INTERDITE = 103;
    private const LIMITATION_DE_VITESSE = 113;
    private const SENS_INTERDIT_OU_SENS_UNIQUE = 125;

    public function __construct(
        public readonly int $fileId,
        public readonly string $nom,
    ) {
    }

    public static function listCols(): array
    {
        return [
            self::NOM,
        ];
    }

    public static function getNomChoices(): array
    {
        return [
            self::CIRCULATION_ALTERNEE,
            self::CIRCULATION_INTERDITE,
            self::LIMITATION_DE_VITESSE,
            self::SENS_INTERDIT_OU_SENS_UNIQUE,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            fileId: $row['fileId'],
            nom: $row['fields'][self::NOM],
        );
    }

    public function asCommand(): SaveMeasureCommand
    {
        $command = new SaveMeasureCommand();

        $command->type = match ($this->nom) {
            'circulation alternée' => MeasureTypeEnum::ALTERNATE_ROAD->value,
            'circulation interdite' => MeasureTypeEnum::NO_ENTRY->value,
            'limitation de vitesse' => MeasureTypeEnum::SPEED_LIMITATION->value,
            'sens interdit (ou sens unique)' => MeasureTypeEnum::ONE_WAY_TRAFFIC->value,
            default => throw new SkipException(sprintf("Unexpected measure type name: '%s'", $this->nom)),
        };

        return $command;
    }
}
