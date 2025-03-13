<?php

declare(strict_types=1);

namespace App\Application;

interface OrganizationAdministrativeBoundariesGeometryInterface
{
    public function findByCodes(string $code, string $codeType): string;
}
