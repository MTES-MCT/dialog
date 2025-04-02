<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Repository\AdministratorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdministratorRepositoryTest extends KernelTestCase
{
    public function testFindAll(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var AdministratorRepositoryInterface */
        $repository = $container->get(AdministratorRepositoryInterface::class);

        $administrators = $repository->findAll();

        $this->assertEquals([RoadTypeEnum::DEPARTMENTAL_ROAD->value, RoadTypeEnum::NATIONAL_ROAD->value], array_keys($administrators));

        $this->assertCount(107, $administrators[RoadTypeEnum::DEPARTMENTAL_ROAD->value]);
        $this->assertCount(24, $administrators[RoadTypeEnum::NATIONAL_ROAD->value]);

        // Take some examples
        $this->assertContains('Nord', $administrators[RoadTypeEnum::DEPARTMENTAL_ROAD->value]);
        $this->assertContains('Calvados', $administrators[RoadTypeEnum::DEPARTMENTAL_ROAD->value]);
        $this->assertContains('DIR Ouest', $administrators[RoadTypeEnum::NATIONAL_ROAD->value]);
        $this->assertContains('ATB', $administrators[RoadTypeEnum::NATIONAL_ROAD->value]);
    }
}
