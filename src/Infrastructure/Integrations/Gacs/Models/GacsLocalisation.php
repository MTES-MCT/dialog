<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations\Gacs\Models;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Integrations\Gacs\Exception\SkipException;

final class GacsLocalisation
{
    public const TAB_ID = 2700;
    public const PORTE_SUR = 2705;
    public const ARRONDISSEMENT = 2708;
    public const LIBELLE_VOIE = 2710;
    public const LIBELLE_VOIE_DEBUT = 2730;
    public const LIBELLE_VOIE_FIN = 2740;
    public const N_ADRESSE = 2755;
    public const N_ADRESSE_DEBUT = 2720;
    public const N_ADRESSE_FIN = 2737;
    public const LIBELLE_ADRESSE_DEBUT = 2736;
    public const LIBELLE_ADRESSE_FIN = 2751;

    private const ARRONDISSEMENT_REGEX = '/^(?<value>\d+)(er|e|ème|eme)\s+arrondissement$/i';
    private const LIBELLE_ADRESSE_ROAD_NAME_REGEX = '/^(?<houseNumber>\d+\s*(bis|b|ter|t|quater|q)?)\s+(?<roadName>.*)$/i';
    private const POINT_HOUSE_NUMBERS_REGEX = '/([nN°]\s*)?(?<fromHouseNumber>\d+\s*(bis|b|ter|t|quater|q)?)(\s+(au|à|et)\s+([nN]°\s*)?(<?toHouseNumber>\d+\s*(bis|b|ter|t|quater|q)?))?$/i';

    public function __construct(
        public readonly int $fileId,
        public readonly string $porteSur,
        public readonly ?int $arrondissement,
        public readonly ?string $libelleVoie,
        public readonly ?string $libelleVoieDebut,
        public readonly ?string $libelleVoieFin,
        public readonly ?string $libelleAdresseDebut,
        public readonly ?string $libelleAdresseFin,
        public readonly ?string $numAdresse,
        public readonly ?string $numAdresseDebut,
        public readonly ?string $numAdresseFin,
    ) {
    }

    public static function listCols(): array
    {
        return [
            self::PORTE_SUR,
            self::ARRONDISSEMENT,
            self::LIBELLE_VOIE,
            self::LIBELLE_VOIE_DEBUT,
            self::LIBELLE_VOIE_FIN,
            self::LIBELLE_ADRESSE_DEBUT,
            self::LIBELLE_ADRESSE_FIN,
            self::N_ADRESSE,
            self::N_ADRESSE_DEBUT,
            self::N_ADRESSE_FIN,
        ];
    }

    private static function parseArrondissement(?string $value): int
    {
        if (!$value) {
            throw new SkipException('ARRONDISSEMENT is empty');
        }

        $matches = [];

        if (!preg_match(self::ARRONDISSEMENT_REGEX, $value, $matches)) {
            $msg = sprintf("ARRONDISSEMENT value '%s' did not have expected format '%s'", $value, self::ARRONDISSEMENT_REGEX);
            throw new SkipException($msg);
        }

        return (int) $matches['value'];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            fileId: $row['fileId'],
            porteSur: $row['fields'][self::PORTE_SUR],
            arrondissement: self::parseArrondissement($row['fields'][self::ARRONDISSEMENT]),
            libelleVoie: $row['fields'][self::LIBELLE_VOIE],
            libelleVoieDebut: $row['fields'][self::LIBELLE_VOIE_DEBUT],
            libelleVoieFin: $row['fields'][self::LIBELLE_VOIE_FIN],
            libelleAdresseDebut: $row['fields'][self::LIBELLE_ADRESSE_DEBUT],
            libelleAdresseFin: $row['fields'][self::LIBELLE_ADRESSE_FIN],
            numAdresse: $row['fields'][self::N_ADRESSE],
            numAdresseDebut: $row['fields'][self::N_ADRESSE_DEBUT],
            numAdresseFin: $row['fields'][self::N_ADRESSE_FIN],
        );
    }

    private function getRoadInfo(): array
    {
        if (
            $this->porteSur === 'La totalité de la voie'
            && $this->libelleVoie
        ) {
            return [
                'roadName' => $this->libelleVoie,
                'fromHouseNumber' => null,
                'toHouseNumber' => null,
            ];
        }

        if (
            $this->porteSur === 'Une section'
            && $this->libelleVoie
            && (!$this->libelleVoieDebut || ($this->libelleVoieDebut === $this->libelleVoie))
            && (!$this->libelleVoieFin || ($this->libelleVoieFin === $this->libelleVoie))
            && (!$this->libelleAdresseDebut || (
                preg_match(self::LIBELLE_ADRESSE_ROAD_NAME_REGEX, $this->libelleAdresseDebut, $debutMatches)
                && $debutMatches['roadName'] === $this->libelleVoie
            ))
            && (!$this->libelleAdresseFin || (
                preg_match(self::LIBELLE_ADRESSE_ROAD_NAME_REGEX, $this->libelleAdresseFin, $finMatches)
                && $finMatches['roadName'] === $this->libelleVoie
            ))
        ) {
            return [
                'roadName' => $this->libelleVoie,
                'fromHouseNumber' => $this->numAdresseDebut ?? null,
                'toHouseNumber' => $this->numAdresseFin ?? null,
            ];
        }

        if (
            $this->libelleAdresseDebut
            && $this->libelleAdresseFin
            && preg_match(self::LIBELLE_ADRESSE_ROAD_NAME_REGEX, $this->libelleAdresseDebut, $debutMatches)
            && preg_match(self::LIBELLE_ADRESSE_ROAD_NAME_REGEX, $this->libelleAdresseFin, $finMatches)
            && $debutMatches['roadName']
            && $finMatches['roadName']
            && ($debutMatches['roadName'] === $finMatches['roadName'])
            && (
                $this->porteSur === 'Une section'
                || (
                    $this->porteSur === 'Une zone'
                    && strtolower($this->libelleVoie) === strtolower($debutMatches['roadName'])
                )
            )
        ) {
            return [
                'roadName' => $debutMatches['roadName'],
                'fromHouseNumber' => $debutMatches['houseNumber'] ?? null,
                'toHouseNumber' => $finMatches['houseNumber'] ?? null,
            ];
        }

        if (
            $this->porteSur === 'Un point'
            && $this->libelleVoie
            && $this->numAdresse
            && preg_match(self::POINT_HOUSE_NUMBERS_REGEX, $this->numAdresse, $pointMatches)
        ) {
            return [
                'roadName' => $this->libelleVoie,
                'fromHouseNumber' => $pointMatches['fromHouseNumber'],
                'toHouseNumber' => empty($pointMatches['toHouseNumber']) ? $pointMatches['fromHouseNumber'] : $pointMatches['toHouseNumber'],
            ];
        }

        throw new SkipException(sprintf('Could not extract road info from: %s', var_export($this, return: true)));
    }

    public function asCommand(RegulationOrderRecord $regulationOrderRecord, SaveMeasureCommand $measureCommand): SaveRegulationLocationCommand
    {
        $command = new SaveRegulationLocationCommand($regulationOrderRecord);

        $roadInfo = $this->getRoadInfo();

        $locationAddress = new LocationAddress(
            postCode: sprintf('750%s', str_pad((string) $this->arrondissement, 2, '0', STR_PAD_LEFT)),
            city: 'Paris',
            roadName: $roadInfo['roadName'],
        );

        $command->address = (string) $locationAddress;
        $command->fromHouseNumber = $roadInfo['fromHouseNumber'];
        $command->toHouseNumber = $roadInfo['toHouseNumber'];
        $command->measures[] = $measureCommand;

        return $command;
    }
}
