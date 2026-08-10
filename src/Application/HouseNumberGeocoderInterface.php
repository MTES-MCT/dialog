<?php

declare(strict_types=1);

namespace App\Application;

interface HouseNumberGeocoderInterface
{
    /**
     * Récupère la liste des numéros de voie (numéro + suffixe) connus pour une voie
     * identifiée par son identifiant BAN.
     *
     * @return string[] Les numéros ordonnés (ex : ['1', '2', '6bis', '10'])
     */
    public function findHouseNumbers(string $roadBanId): array;
}
