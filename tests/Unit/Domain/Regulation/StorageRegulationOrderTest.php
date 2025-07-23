<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\StorageRegulationOrder;
use PHPUnit\Framework\TestCase;

final class StorageRegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $storageRegulationOrder = (new StorageRegulationOrder(
            uuid: '666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3',
            regulationOrder: $regulationOrder,
            path: '/path/to/regulationOrder.pdf',
            url: 'https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb.pdf',
            title: 'Titre du document',
            fileSize: 123456,
            mimeType: 'application/pdf',
        ));

        $this->assertSame('666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3', $storageRegulationOrder->getUuid());
        $this->assertSame($regulationOrder, $storageRegulationOrder->getRegulationOrder());
        $this->assertSame('/path/to/regulationOrder.pdf', $storageRegulationOrder->getPath());
        $this->assertSame('https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb.pdf', $storageRegulationOrder->getUrl());
        $this->assertSame('Titre du document', $storageRegulationOrder->getTitle());
        $this->assertSame(123, $storageRegulationOrder->getFileSize());
        $this->assertSame('PDF', $storageRegulationOrder->getMimeType());
        $storageRegulationOrder->update(
            path: '/path/to/regulationOrder2.pdf',
            url: 'https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb2.pdf',
            title: 'Titre du document 2',
            fileSize: 654321,
            mimeType: 'image/jpeg',
        );

        $this->assertSame('/path/to/regulationOrder2.pdf', $storageRegulationOrder->getPath());
        $this->assertSame('https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb2.pdf', $storageRegulationOrder->getUrl());
        $this->assertSame('Titre du document 2', $storageRegulationOrder->getTitle());
        $this->assertSame(654, $storageRegulationOrder->getFileSize());
        $this->assertSame('JPG', $storageRegulationOrder->getMimeType());
    }
}
