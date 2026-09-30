<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\View\FetchedAdministrativeBoundaryView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;

interface AdministrativeBoundaryFetcherInterface
{
    /**
     * Récupère le contour officiel d'une collectivité à partir de son code du COG.
     *
     * @throws AdministrativeBoundaryNotFoundException    si le code est inconnu du COG
     * @throws AdministrativeBoundaryUnavailableException si la source de données est indisponible
     */
    public function fetch(OrganizationCodeTypeEnum $codeType, string $code): FetchedAdministrativeBoundaryView;
}
