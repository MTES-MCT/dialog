<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller;

use App\Infrastructure\Controller\AdministrativeBoundaryQueryParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

final class AdministrativeBoundaryQueryParametersTest extends TestCase
{
    public function testFromRequestWithoutParameters(): void
    {
        $this->assertSame([], AdministrativeBoundaryQueryParameters::fromRequest(new Request(['embed' => '1'])));
    }

    public function testFromRequestIndexesNormalizedCodesByType(): void
    {
        $request = new Request([
            'inseeCode' => '44109',
            'epciCode' => '244400404',
            'departmentCode' => ' 2a ',
            'regionCode' => '',
            'organizationUuid' => '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22',
        ]);

        $this->assertSame(
            [
                'insee' => '44109',
                'epci' => '244400404',
                'departement' => '2A',
            ],
            AdministrativeBoundaryQueryParameters::fromRequest($request),
        );
    }

    public function testFromRequestRejectsNonScalarParameter(): void
    {
        $this->expectException(BadRequestException::class);

        AdministrativeBoundaryQueryParameters::fromRequest(new Request(['departmentCode' => ['44']]));
    }
}
