<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Statistics\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Statistics\Command\RecordApiUsageCommand;
use App\Application\Statistics\Command\RecordApiUsageCommandHandler;
use App\Domain\Statistics\ApiUsageDaily;
use App\Domain\Statistics\Repository\ApiUsageDailyRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RecordApiUsageCommandHandlerTest extends TestCase
{
    private MockObject&ApiUsageDailyRepositoryInterface $apiUsageDailyRepository;
    private MockObject&DateUtilsInterface $dateUtils;
    private MockObject&IdFactoryInterface $idFactory;

    protected function setUp(): void
    {
        $this->apiUsageDailyRepository = $this->createMock(ApiUsageDailyRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
    }

    public function testAddsNewEntityWhenNoneExists(): void
    {
        $command = new RecordApiUsageCommand(type: 'cifs');
        $day = new \DateTimeImmutable('2025-03-15 14:30:00');
        $fixedUuid = '11111111-2222-3333-4444-555555555555';

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($day);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn($fixedUuid);

        $this->apiUsageDailyRepository
            ->expects(self::once())
            ->method('findOneByDayAndType')
            ->with(
                self::callback(fn (\DateTimeInterface $d) => $d->format('Y-m-d') === '2025-03-15'),
                'cifs',
            )
            ->willReturn(null);

        $this->apiUsageDailyRepository
            ->expects(self::once())
            ->method('add')
            ->with(self::callback(function (ApiUsageDaily $entity): bool {
                return $entity->getUuid() === '11111111-2222-3333-4444-555555555555'
                    && $entity->getDay()->format('Y-m-d') === '2025-03-15'
                    && $entity->getType() === 'cifs'
                    && $entity->getCount() === 1;
            }));

        $handler = new RecordApiUsageCommandHandler(
            apiUsageDailyRepository: $this->apiUsageDailyRepository,
            dateUtils: $this->dateUtils,
            idFactory: $this->idFactory,
        );

        ($handler)($command);
    }

    public function testIncrementsCountWhenEntityExists(): void
    {
        $command = new RecordApiUsageCommand(type: 'datex');
        $day = new \DateTimeImmutable('2025-03-15 00:00:00');
        $existing = new ApiUsageDaily(uuid: '00000000-0000-0000-0000-000000000000', day: $day, type: 'datex', count: 42);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($day);

        $this->apiUsageDailyRepository
            ->expects(self::once())
            ->method('findOneByDayAndType')
            ->with($day, 'datex')
            ->willReturn($existing);

        $this->apiUsageDailyRepository
            ->expects(self::never())
            ->method('add');

        $handler = new RecordApiUsageCommandHandler(
            apiUsageDailyRepository: $this->apiUsageDailyRepository,
            dateUtils: $this->dateUtils,
            idFactory: $this->idFactory,
        );

        ($handler)($command);

        self::assertSame(43, $existing->getCount());
    }
}
