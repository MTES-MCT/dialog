<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LocationRepositoryTest extends KernelTestCase
{
    public function testFindAllOneWaySections(): void
    {
        static::bootKernel();

        /** @var LocationRepositoryInterface */
        $repository = static::getContainer()->get(LocationRepositoryInterface::class);

        $locations = $repository->findAllOneWaySections();

        // Seule emprise des fixtures à sens unique avec une section : Rue Eugène Berthoud, du 47 au 65, de A vers B.
        // Les voies entières et les emprises à double sens sont exclues.
        $this->assertCount(1, $locations);

        /** @var Location $location */
        $location = $locations[0];
        $this->assertSame(LocationFixture::UUID_TYPICAL, $location->getUuid());
        $this->assertSame(DirectionEnum::A_TO_B->value, $location->getNamedStreet()->getDirection());
        $this->assertSame('47', $location->getNamedStreet()->getFromHouseNumber());
    }
}
