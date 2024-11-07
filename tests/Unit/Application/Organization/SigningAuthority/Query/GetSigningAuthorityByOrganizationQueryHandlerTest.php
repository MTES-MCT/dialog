<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\SigningAuthority\Query;

use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQuery;
use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQueryHandler;
use App\Domain\Organization\SigningAuthority\Repository\SigningAuthorityRepositoryInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;
use PHPUnit\Framework\TestCase;

final class GetSigningAuthorityByOrganizationQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $signingAuthority = $this->createMock(SigningAuthority::class);

        $signingAuthorityRepository = $this->createMock(SigningAuthorityRepositoryInterface::class);
        $signingAuthorityRepository
            ->expects(self::once())
            ->method('findOneByOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($signingAuthority);

        $handler = new GetSigningAuthorityByOrganizationQueryHandler($signingAuthorityRepository);
        $result = $handler(new GetSigningAuthorityByOrganizationQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($signingAuthority, $result);
    }
}
