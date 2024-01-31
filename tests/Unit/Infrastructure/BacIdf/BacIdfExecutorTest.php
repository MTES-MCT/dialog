<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use App\Infrastructure\BacIdf\BacIdfExecutor;
use App\Infrastructure\BacIdf\BacIdfExtractor;
use App\Infrastructure\BacIdf\BacIdfTransformer;
use App\Infrastructure\BacIdf\BacIdfTransformerResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BacIdfExecutorTest extends TestCase
{
    private $logger;
    private $extractor;
    private $transformer;
    private $commandBus;
    private $queryBus;
    private $regulationOrderRecordRepository;
    private $orgId = '064f5eba-5eb2-7ffd-8000-77e8f8b7bb9b';
    private $dateUtils;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->extractor = $this->createMock(BacIdfExtractor::class);
        $this->transformer = $this->createMock(BacIdfTransformer::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecute(): void
    {
        $organization = $this->createMock(Organization::class);

        $executor = new BacIdfExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
            $this->dateUtils,
        );

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSourceInOrganization')
            ->with('bacidf', $organization)
            ->willReturn(['064ef37c-170f-7737-8000-d6f3b1db7685']);

        $record1 = ['fields' => [1101 => '20210104']];
        $importCommand1 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $result1 = new BacIdfTransformerResult($importCommand1, []);

        $record2 = ['fields' => [1101 => '20210305']];
        $result2 = new BacIdfTransformerResult(null, [['reason' => 'no_locations_gathered']]);

        $record3 = ['fields' => [1101 => '20210415']];
        $importCommand3 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $result3 = new BacIdfTransformerResult($importCommand3, []);

        $records = [$record1, $record2, $record3];

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with(['064ef37c-170f-7737-8000-d6f3b1db7685'])
            ->willReturn((fn () => yield from $records)());

        $transformMatcher = self::exactly(3);
        $this->transformer
            ->expects($transformMatcher)
            ->method('transform')
            ->willReturnCallback(fn ($record, $org) => match ($transformMatcher->getInvocationCount()) {
                1 => $this->assertEquals($record1, $record) ?: $this->assertEquals($organization, $org) ?: $result1,
                2 => $this->assertEquals($record2, $record) ?: $this->assertEquals($organization, $org) ?: $result2,
                3 => $this->assertEquals($record3, $record) ?: $this->assertEquals($organization, $org) ?: $result3,
            });

        $handleMatcher = self::exactly(2);
        $this->commandBus
            ->expects($handleMatcher)
            ->method('handle')
            ->willReturnCallback(fn ($command) => match ($handleMatcher->getInvocationCount()) {
                1 => $this->assertEquals($importCommand1, $command),
                2 => $this->assertEquals($importCommand3, $command),
            });

        $timeMatcher = self::exactly(2);
        $this->dateUtils
            ->expects($timeMatcher)
            ->method('getMicroTime')
            ->willReturnCallback(fn () => match ($timeMatcher->getInvocationCount()) {
                1 => 1695218778.6387,
                2 => 1695218796.3069,
            });

        $logMatcher = self::exactly(5);
        $this->logger
            ->expects($logMatcher)
            ->method('info')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'started'),
                2 => $this->assertEquals($message, 'created'),
                3 => $this->assertEquals($message, 'skipped'),
                4 => $this->assertEquals($message, 'created'),
                5 => (
                    $this->assertEquals($message, 'done')
                    ?: $this->assertEquals($context, [
                        'numProcessed' => 3,
                        'numCreated' => 2,
                        'percentCreated' => 66.7,
                        'numSkipped' => 1,
                        'percentSkipped' => 33.3,
                        'numErrors' => 0,
                        'percentErrors' => 0.0,
                        'elapsedSeconds' => 17.67,
                    ])
                ),
            });

        $executor->execute();
    }
}
