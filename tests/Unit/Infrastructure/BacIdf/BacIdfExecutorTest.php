<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\BacIdf\BacIdfExecutor;
use App\Infrastructure\BacIdf\BacIdfExtractor;
use App\Infrastructure\BacIdf\BacIdfTransformer;
use App\Infrastructure\BacIdf\BacIdfTransformerResult;
use App\Infrastructure\BacIdf\Exception\BacIdfException;
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

        $record1 = ['ARR_REF' => 'arr_1'];
        $importCommand1 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $result1 = new BacIdfTransformerResult($importCommand1, []);

        $record2 = ['ARR_REF' => 'arr_2'];
        $result2 = new BacIdfTransformerResult(null, [['reason' => 'no_locations_gathered']]);

        $record3 = ['ARR_REF' => 'arr_3'];
        $importCommand3 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $result3 = new BacIdfTransformerResult($importCommand3, []);

        $record4 = ['ARR_REF' => 'arr_4'];
        $result4 = new BacIdfTransformerResult(null, [['reason' => 'value_not_expected', 'expected' => 'CIRCULATION']]);

        $records = [$record1, $record2, $record3, $record4];

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with(['064ef37c-170f-7737-8000-d6f3b1db7685'])
            ->willReturn((fn () => yield from $records)());

        $transformMatcher = self::exactly(4);
        $this->transformer
            ->expects($transformMatcher)
            ->method('transform')
            ->willReturnCallback(fn ($record, $org) => match ($transformMatcher->getInvocationCount()) {
                1 => $this->assertEquals($record1, $record) ?: $this->assertEquals($organization, $org) ?: $result1,
                2 => $this->assertEquals($record2, $record) ?: $this->assertEquals($organization, $org) ?: $result2,
                3 => $this->assertEquals($record3, $record) ?: $this->assertEquals($organization, $org) ?: $result3,
                4 => $this->assertEquals($record4, $record) ?: $this->assertEquals($organization, $org) ?: $result4,
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

        $logMatcher = self::exactly(6);
        $this->logger
            ->expects($logMatcher)
            ->method('info')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'started'),
                2 => $this->assertEquals($message, 'created'),
                3 => $this->assertEquals($message, 'skipped'),
                4 => $this->assertEquals($message, 'created'),
                5 => $this->assertEquals($message, 'skipped'),
                6 => (
                    $this->assertEquals($message, 'done')
                    ?: $this->assertEquals([
                        'numProcessed' => 4,
                        'numCreated' => 2,
                        'percentCreated' => 50,
                        'numSkipped' => 2,
                        'numSkippedNotCirculation' => 1,
                        'percentSkipped' => 50,
                        'numErrors' => 0,
                        'percentErrors' => 0.0,
                        'elapsedSeconds' => 17.67,
                    ], $context)
                ),
            });

        $executor->execute();
    }

    public function testExecuteEmpty(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSourceInOrganization')
            ->with('bacidf', $organization)
            ->willReturn([]);

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with([])
            ->willReturn(new \EmptyIterator());

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

        $timeMatcher = self::exactly(2);
        $this->dateUtils
            ->expects($timeMatcher)
            ->method('getMicroTime')
            ->willReturnCallback(fn () => match ($timeMatcher->getInvocationCount()) {
                1 => 1695218778.6387,
                2 => 1695218779.3069,
            });

        $logMatcher = self::exactly(2);
        $this->logger
            ->expects($logMatcher)
            ->method('info')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'started'),
                2 => (
                    $this->assertEquals($message, 'done')
                    ?: $this->assertEquals([
                        'numProcessed' => 0,
                        'numCreated' => 0,
                        'percentCreated' => 0,
                        'numSkipped' => 0,
                        'numSkippedNotCirculation' => 0,
                        'percentSkipped' => 0,
                        'numErrors' => 0,
                        'percentErrors' => 0,
                        'elapsedSeconds' => 0.67,
                    ], $context)
                ),
            });

        $executor->execute();
    }

    public function testExecuteOrganizationDoesNotExist(): void
    {
        $this->expectException(BacIdfException::class);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willThrowException(new OrganizationNotFoundException('my_message'));

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findIdentifiersForSourceInOrganization');

        $this->extractor
            ->expects(self::never())
            ->method('iterExtract');

        $this->logger
            ->expects(self::exactly(1))
            ->method('error');

        $timeMatcher = self::exactly(2);
        $this->dateUtils
            ->expects($timeMatcher)
            ->method('getMicroTime')
            ->willReturnCallback(fn () => match ($timeMatcher->getInvocationCount()) {
                1 => 1695218778.6387,
                2 => 1695218796.3069,
            });

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

        $executor->execute();
    }

    public function testExecuteImportFailed(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

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

        $record1 = ['ARR_REF' => 'arr_1'];
        $importCommand1 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $result1 = new BacIdfTransformerResult($importCommand1, []);

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with([])
            ->willReturn((fn () => yield from [$record1])());

        $this->transformer
            ->expects(self::once())
            ->method('transform')
            ->with($record1)
            ->willReturn($result1);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->willThrowException(new ImportBacIdfRegulationFailedException('Failure'));

        $timeMatcher = self::exactly(2);
        $this->dateUtils
            ->expects($timeMatcher)
            ->method('getMicroTime')
            ->willReturnCallback(fn () => match ($timeMatcher->getInvocationCount()) {
                1 => 1695218778.6387,
                2 => 1695218796.3069,
            });

        $logMatcher = self::exactly(2);
        $this->logger
            ->expects($logMatcher)
            ->method('info')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'started'),
                2 => (
                    $this->assertEquals($message, 'done')
                    ?: $this->assertEquals([
                        'numProcessed' => 1,
                        'numCreated' => 0,
                        'percentCreated' => 0,
                        'numSkipped' => 0,
                        'numSkippedNotCirculation' => 0,
                        'percentSkipped' => 0,
                        'numErrors' => 1,
                        'percentErrors' => 100,
                        'elapsedSeconds' => 17.67,
                    ], $context)
                ),
            });

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('failed', self::anything());

        $executor->execute();
    }

    public function testExecuteOrgIdMissing(): void
    {
        $this->expectException(BacIdfException::class);
        $this->expectExceptionMessageMatches("/Please set APP_BAC_IDF_ORG_ID in \.env\.local/");

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findIdentifiersForSourceInOrganization');

        $this->extractor
            ->expects(self::never())
            ->method('iterExtract');

        $this->logger
            ->expects(self::never())
            ->method('debug');

        $this->dateUtils
            ->expects(self::never())
            ->method('getMicroTime');

        $executor = new BacIdfExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            '',
            $this->dateUtils,
        );

        $executor->execute();
    }
}
