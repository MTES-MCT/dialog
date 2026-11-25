<?php

declare(strict_types=1);

namespace App\Domain\Anct\Repository;

interface TerritoryContactRepositoryInterface
{
    /**
     * Returns true if the ANCT reference data lists the given email as the
     * official e-mail address of the territory identified by the given SIRET.
     */
    public function isWhitelisted(string $siret, string $email): bool;
}
