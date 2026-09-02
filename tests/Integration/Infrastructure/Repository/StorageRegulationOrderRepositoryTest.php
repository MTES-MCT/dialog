<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StorageRegulationOrderRepositoryTest extends KernelTestCase
{
    private const TYPICAL_REGULATION_ORDER_RECORD_UUID = 'e413a47e-5928-4353-a8b2-8b7dda27f9a5';

    private StorageRegulationOrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->repository = $container->get(StorageRegulationOrderRepositoryInterface::class);
    }

    public function testGetStoragesByRegulationOrderRecordUuidsEmpty(): void
    {
        $this->assertSame([], $this->repository->getStoragesByRegulationOrderRecordUuids([]));
    }

    public function testGetStoragesByRegulationOrderRecordUuids(): void
    {
        $result = $this->repository->getStoragesByRegulationOrderRecordUuids([self::TYPICAL_REGULATION_ORDER_RECORD_UUID]);

        $this->assertArrayHasKey(self::TYPICAL_REGULATION_ORDER_RECORD_UUID, $result);
        $this->assertSame('/files/storage1.pdf', $result[self::TYPICAL_REGULATION_ORDER_RECORD_UUID]['path']);
        $this->assertSame('https://example.com/storage1.pdf', $result[self::TYPICAL_REGULATION_ORDER_RECORD_UUID]['url']);
    }

    public function testGetStoragesByRegulationOrderRecordUuidsUnknownUuid(): void
    {
        $result = $this->repository->getStoragesByRegulationOrderRecordUuids(['00000000-0000-0000-0000-000000000000']);

        $this->assertSame([], $result);
    }
}
