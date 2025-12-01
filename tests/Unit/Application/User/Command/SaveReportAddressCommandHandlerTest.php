<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Application\User\Command\SaveReportAddressCommandHandler;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveReportAddressCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $reportAddressRepository;
    private MockObject $dateUtils;
    private MockObject $user;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->reportAddressRepository = $this->createMock(ReportAddressRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->user = $this->createMock(User::class);
    }

    public function testHandle(): void
    {
        $command = new SaveReportAddressCommand($this->user);
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->roadType = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $reportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            roadType: 'Route départementale - D12',
            user: $this->user,
        );
        $reportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($reportAddress,
            ));

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
        );
        $handler($command);
    }
}
