<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\AccessRequest;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Specification\IsAccessAlreadyRequested;
use PHPUnit\Framework\TestCase;

final class IsAccessAlreadyRequestedTest extends TestCase
{
    public function testAccessAlreadyRequested(): void
    {
        $accessRequest = $this->createMock(AccessRequest::class);
        $accessRequestRepository = $this->createMock(AccessRequestRepositoryInterface::class);
        $accessRequestRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn($accessRequest);

        $pattern = new IsAccessAlreadyRequested($accessRequestRepository);
        $this->assertTrue($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr'));
    }

    public function testAccessNotRequested(): void
    {
        $accessRequestRepository = $this->createMock(AccessRequestRepositoryInterface::class);
        $accessRequestRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn(null);

        $pattern = new IsAccessAlreadyRequested($accessRequestRepository);
        $this->assertFalse($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr'));
    }
}
