<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\StorageRegulationOrder;
use PHPUnit\Framework\TestCase;

final class StorageRegulationOrderTest extends TestCase
{
    public function testGetters(): void
    {
        $storageRegulationOrder = (new StorageRegulationOrder(
            uuid: '666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3',
            path: '/path/to/regulationOrder.pdf',
            url: 'https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb.pdf',
        ));

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $storageRegulationOrder->getUuid());
        $this->assertSame('/path/to/regulationOrder.pdf', $storageRegulationOrder->getPath());
        $this->assertSame('https://dialog.oos.cloudgouv-eu-west-1.outscale.com/regulationOrder/666a4b2c-55cc-43d1-bb7f-4986f2b2f5f3/php4fojLb.pdf', $storageRegulationOrder->getUrl());
    }
}
