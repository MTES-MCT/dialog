<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\EudonetParis\EudonetParisExecutor;
use App\Infrastructure\EudonetParis\EudonetParisExtractor;
use App\Infrastructure\EudonetParis\EudonetParisTransformer;
use App\Infrastructure\EudonetParis\EudonetParisTransformerResult;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EudonetParisExecutorTest extends TestCase
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
        $this->extractor = $this->createMock(EudonetParisExtractor::class);
        $this->transformer = $this->createMock(EudonetParisTransformer::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecute(): void
    {
        $now = new \DateTimeImmutable('now');
        $organization = $this->createMock(Organization::class);

        $executor = new EudonetParisExecutor(
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
            ->method('findIdentifiersForSource')
            ->willReturn(['064ef37c-170f-7737-8000-d6f3b1db7685']);

        $record1 = ['fields' => [1101 => '20210104']];
        $importCommand1 = $this->createMock(ImportEudonetParisRegulationCommand::class);
        $result1 = new EudonetParisTransformerResult($importCommand1, []);

        $record2 = ['fields' => [1101 => '20210305']];
        $result2 = new EudonetParisTransformerResult(null, [['reason' => 'no_locations_gathered']]);

        $record3 = ['fields' => [1101 => '20210415']];
        $importCommand3 = $this->createMock(ImportEudonetParisRegulationCommand::class);
        $result3 = new EudonetParisTransformerResult($importCommand3, []);

        $records = [$record1, $record2, $record3];

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with($now, ['064ef37c-170f-7737-8000-d6f3b1db7685'])
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
                        'numSkippedNoLocationsGathered' => 1,
                        'percentSkipped' => 33.3,
                        'numErrors' => 0,
                        'percentErrors' => 0,
                        'elapsedSeconds' => 17.67,
                    ])
                ),
            });

        $executor->execute($now);
    }

    public function testExecuteEmpty(): void
    {
        $now = new \DateTimeImmutable('now');
        $organization = $this->createMock(Organization::class);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSource')
            ->with('eudonet_paris')
            ->willReturn([]);

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with($now, [])
            ->willReturn(new \EmptyIterator());

        $executor = new EudonetParisExecutor(
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
                    ?: $this->assertEquals($context, [
                        'numProcessed' => 0,
                        'numCreated' => 0,
                        'percentCreated' => 0,
                        'numSkipped' => 0,
                        'numSkippedNoLocationsGathered' => 0,
                        'percentSkipped' => 0,
                        'numErrors' => 0,
                        'percentErrors' => 0,
                        'elapsedSeconds' => 0.67,
                    ])
                ),
            });

        $executor->execute($now);
    }

    public function testExecuteOrganizationDoesNotExist(): void
    {
        $this->expectException(EudonetParisException::class);

        $now = new \DateTimeImmutable('now');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willThrowException(new OrganizationNotFoundException('my_message'));

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findIdentifiersForSource');

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

        $executor = new EudonetParisExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
            $this->dateUtils,
        );

        $executor->execute($now);
    }

    public function testExecuteImportFailed(): void
    {
        $now = new \DateTimeImmutable('now');

        $organization = $this->createMock(Organization::class);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $executor = new EudonetParisExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
            $this->dateUtils,
        );

        $record1 = ['fields' => [1101 => '20210104']];
        $importCommand1 = $this->createMock(ImportEudonetParisRegulationCommand::class);
        $result1 = new EudonetParisTransformerResult($importCommand1, []);

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with($now, [])
            ->willReturn((fn () => yield from [$record1])());

        $this->transformer
            ->expects(self::once())
            ->method('transform')
            ->with($record1)
            ->willReturn($result1);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->willThrowException(new ImportEudonetParisRegulationFailedException('Failure'));

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
                    ?: $this->assertEquals($context, [
                        'numProcessed' => 1,
                        'numCreated' => 0,
                        'percentCreated' => 0,
                        'numSkipped' => 0,
                        'numSkippedNoLocationsGathered' => 0,
                        'percentSkipped' => 0,
                        'numErrors' => 1,
                        'percentErrors' => 100,
                        'elapsedSeconds' => 17.67,
                    ])
                ),
            });

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('failed', self::anything());

        $executor->execute($now);
    }

    public function testExecuteOrgIdMissing(): void
    {
        $this->expectException(EudonetParisException::class);
        $this->expectExceptionMessageMatches("/Please set APP_EUDONET_PARIS_ORG_ID in \.env\.local/");

        $now = new \DateTimeImmutable('now');

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findIdentifiersForSource');

        $this->extractor
            ->expects(self::never())
            ->method('iterExtract');

        $this->logger
            ->expects(self::never())
            ->method('debug');

        $this->dateUtils
            ->expects(self::never())
            ->method('getMicroTime');

        $executor = new EudonetParisExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            '',
            $this->dateUtils,
        );

        $executor->execute($now);
    }
}
