<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\SaveReportAddressCommand;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveReportAddressCommandTest extends TestCase
{
    private MockObject $user;

    public function setUp(): void
    {
        $this->user = $this->createMock(User::class);
    }

    public function testBuildLocationWithNumberedRoad(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            administrator: 'Route départementale',
            roadNumber: 'D12',
        );

        self::assertSame('Route départementale - D12', $command->location);
    }

    public function testBuildLocationWithNumberedRoadOnlyAdministrator(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            administrator: 'Route départementale',
        );

        self::assertSame('Route départementale', $command->location);
    }

    public function testBuildLocationWithNumberedRoadOnlyRoadNumber(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            roadNumber: 'D12',
        );

        self::assertSame('D12', $command->location);
    }

    public function testBuildLocationWithNamedStreet(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            cityLabel: 'Paris',
            roadName: 'Rue de la Paix',
        );

        self::assertSame('Paris - Rue de la Paix', $command->location);
    }

    public function testBuildLocationWithNamedStreetOnlyCityLabel(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            cityLabel: 'Paris',
        );

        self::assertSame('Paris', $command->location);
    }

    public function testBuildLocationWithNamedStreetOnlyRoadName(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            roadName: 'Rue de la Paix',
        );

        self::assertSame('Rue de la Paix', $command->location);
    }

    public function testBuildLocationWithEmptyStrings(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            administrator: '',
            roadNumber: '',
            cityLabel: '',
            roadName: '',
        );

        self::assertNull($command->location);
    }

    public function testBuildLocationWithNullParameters(): void
    {
        $command = new SaveReportAddressCommand($this->user);

        self::assertNull($command->location);
    }

    public function testBuildLocationMethodDirectly(): void
    {
        $command = new SaveReportAddressCommand($this->user);

        $result = $command->buildLocation(
            administrator: 'Route nationale',
            roadNumber: 'N7',
        );

        self::assertSame('Route nationale - N7', $result);
    }

    public function testBuildLocationMethodWithEmptyStrings(): void
    {
        $command = new SaveReportAddressCommand($this->user);

        $result = $command->buildLocation(
            administrator: '',
            roadNumber: '',
        );

        self::assertNull($result);
    }
}
