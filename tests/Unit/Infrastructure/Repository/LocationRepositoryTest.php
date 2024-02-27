<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Domain\Regulation\Location;
use App\Infrastructure\Persistence\Doctrine\Repository\Regulation\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class LocationRepositoryTest extends TestCase
{
    // Coverage only
    public function testDelete(): void
    {
        $location = $this->createMock(Location::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('remove')
            ->with($location);
        $em
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(Location::class));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Location::class)
            ->willReturn($em);

        $repository = new LocationRepository($registry);

        $repository->delete($location);
    }
}
