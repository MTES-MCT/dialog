<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testGetters(): void
    {
        $date = new \DateTimeImmutable('2024-05-07');

        $organization = (new Organization('6598fd41-85cb-42a6-9693-1bc45f4dd392'))
            ->setCreatedAt($date)
            ->setName('Mairie de Savenay')
            ->setSiret('21440195200129')
            ->setLogo('/path/to/logo.jpg')
            ->setCode('44260')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value)
            ->setGeometry('geometry')
            ->setUpdatedAt($date);

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $organization->getUuid());
        $this->assertEquals($date, $organization->getCreatedAt());
        $this->assertSame('Mairie de Savenay', $organization->getName());
        $this->assertSame('21440195200129', $organization->getSiret());
        $this->assertSame('Mairie de Savenay', (string) $organization);
        $this->assertSame('/path/to/logo.jpg', $organization->getLogo());
        $this->assertSame($date, $organization->getUpdatedAt());
        $this->assertSame('44260', $organization->getCode());
        $this->assertSame(OrganizationCodeTypeEnum::INSEE->value, $organization->getCodeType());
        $this->assertSame('geometry', $organization->getGeometry());
        $this->assertSame('44260 (insee)', $organization->getCodeWithType());
    }
}
