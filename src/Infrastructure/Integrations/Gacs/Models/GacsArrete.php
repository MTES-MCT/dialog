<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations\Gacs\Models;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;
use App\Infrastructure\Integrations\Gacs\Exception\SkipException;

final class GacsArrete
{
    public const TAB_ID = 1100;
    public const ID = 1101;
    public const COMPLEMENT_DE_TITRE = 1102;
    public const TYPE = 1108;
    public const DATE_DEBUT = 1109;
    public const DATE_FIN = 1110;
    public const SIGNE_LE = 1111;
    public const CREE_LE = 1195;

    public function __construct(
        public readonly int $fileId,
        public readonly string $id,
        public readonly string $complementDeTitre,
        public readonly string $type,
        public readonly \DateTimeInterface $dateDebut,
        public readonly ?\DateTimeInterface $dateFin,
    ) {
    }

    public static function listCols(): array
    {
        return [
            self::ID,
            self::COMPLEMENT_DE_TITRE,
            self::TYPE,
            self::DATE_DEBUT,
            self::DATE_FIN,
            self::SIGNE_LE,
        ];
    }

    public static function fromRow(array $row): self
    {
        $dateDebutSource = $row['fields'][self::DATE_DEBUT] ?: $row['fields'][self::SIGNE_LE] ?: null;

        if ($dateDebutSource === null) {
            throw new SkipException('DATE_DEBUT is empty');
        }

        $dateDebut = \DateTimeImmutable::createFromFormat(
            'Y/m/d H:i:s',
            $dateDebutSource,
            new \DateTimeZone('Europe/Paris'),
        );

        if ($row['fields'][self::DATE_FIN]) {
            $dateFin = \DateTimeImmutable::createFromFormat(
                'Y/m/d H:i:s', $row['fields'][self::DATE_FIN],
                new \DateTimeZone('Europe/Paris'),
            );
        } else {
            $dateFin = null;
        }

        return new self(
            fileId: $row['fileId'],
            id: $row['fields'][self::ID],
            // TODO: increase character limit?
            // TODO: handle HTML
            complementDeTitre: mb_substr($row['fields'][self::COMPLEMENT_DE_TITRE], 0, 255),
            type: $row['fields'][self::TYPE],
            dateDebut: $dateDebut,
            dateFin: $dateFin,
        );
    }

    public function asCommand(Organization $organization): SaveRegulationGeneralInfoCommand
    {
        $command = new SaveRegulationGeneralInfoCommand();

        // TODO: status

        $command->identifier = $this->id;
        $command->description = $this->complementDeTitre;
        $command->organization = $organization;
        $command->startDate = $this->dateDebut;
        $command->endDate = $this->dateFin;

        if (($type = $this->type) === 'Permanent') {
            $command->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        } else {
            $command->category = RegulationOrderCategoryEnum::OTHER->value;
            $command->otherCategoryText = $type;
        }

        return $command;
    }
}
