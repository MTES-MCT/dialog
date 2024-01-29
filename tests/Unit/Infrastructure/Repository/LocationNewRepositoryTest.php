<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Domain\Regulation\LocationNew;
use App\Infrastructure\Persistence\Doctrine\Repository\Regulation\LocationNewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class LocationNewRepositoryTest extends TestCase
{
    // Coverage only
    public function testDelete(): void
    {
        $locationNew = $this->createMock(LocationNew::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('remove')
            ->with($locationNew);
        $em
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(LocationNew::class));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(LocationNew::class)
            ->willReturn($em);

        $repository = new LocationNewRepository($registry);

        $repository->delete($locationNew);
    }
}
