<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StorageRegulationOrderRepositoryTest extends KernelTestCase
{
    private const TYPICAL_REGULATION_ORDER_UUID = '54eacea0-e1e0-4823-828d-3eae72b76da8';

    private StorageRegulationOrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->repository = $container->get(StorageRegulationOrderRepositoryInterface::class);
    }

    public function testFindPdfInfoByRegulationOrderUuidsEmpty(): void
    {
        $this->assertSame([], $this->repository->findPdfInfoByRegulationOrderUuids([]));
    }

    public function testFindPdfInfoByRegulationOrderUuids(): void
    {
        $result = $this->repository->findPdfInfoByRegulationOrderUuids([self::TYPICAL_REGULATION_ORDER_UUID]);

        $this->assertArrayHasKey(self::TYPICAL_REGULATION_ORDER_UUID, $result);
        $this->assertSame('/files/storage1.pdf', $result[self::TYPICAL_REGULATION_ORDER_UUID]['path']);
        $this->assertSame('https://example.com/storage1.pdf', $result[self::TYPICAL_REGULATION_ORDER_UUID]['url']);
    }

    public function testFindPdfInfoByRegulationOrderUuidsUnknownUuid(): void
    {
        $result = $this->repository->findPdfInfoByRegulationOrderUuids(['00000000-0000-0000-0000-000000000000']);

        $this->assertSame([], $result);
    }
}
