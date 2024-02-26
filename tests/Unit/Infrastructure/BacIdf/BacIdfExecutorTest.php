<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\User\Command\CreateOrganizationCommand;
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
    private $regulationOrderRecordRepository;
    private $dateUtils;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->extractor = $this->createMock(BacIdfExtractor::class);
        $this->transformer = $this->createMock(BacIdfTransformer::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecute(): void
    {
        $organization = $this->createMock(Organization::class);

        $organizationCommand = new CreateOrganizationCommand();
        $organizationCommand->siret = '93123';
        $organizationCommand->name = 'Ville Nouvelle';
        $createdOrganization = $this->createMock(Organization::class);

        $executor = new BacIdfExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
        );

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSource')
            ->with('bacidf')
            ->willReturn(['064ef37c-170f-7737-8000-d6f3b1db7685']);

        $record1 = ['ARR_REF' => 'arr_1'];
        $importCommand1 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $importCommand1->generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $result1 = new BacIdfTransformerResult($importCommand1, [], $organization);

        $record2 = ['ARR_REF' => 'arr_2'];
        $result2 = new BacIdfTransformerResult(null, [['reason' => 'no_measures_found']]);

        $record3 = ['ARR_REF' => 'arr_3'];
        $importCommand3 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $importCommand3->generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $result3 = new BacIdfTransformerResult($importCommand3, [], null, $organizationCommand);

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
            ->willReturnCallback(fn ($record) => match ($transformMatcher->getInvocationCount()) {
                1 => $this->assertEquals($record1, $record) ?: $result1,
                2 => $this->assertEquals($record2, $record) ?: $result2,
                3 => $this->assertEquals($record3, $record) ?: $result3,
                4 => $this->assertEquals($record4, $record) ?: $result4,
            });

        $handleMatcher = self::exactly(3);
        $this->commandBus
            ->expects($handleMatcher)
            ->method('handle')
            ->willReturnCallback(fn ($command) => match ($handleMatcher->getInvocationCount()) {
                1 => $this->assertEquals($importCommand1, $command),
                2 => $this->assertEquals($organizationCommand, $command) ?: $createdOrganization,
                3 => $this->assertEquals($importCommand3, $command),
            });

        $timeMatcher = self::exactly(2);
        $this->dateUtils
            ->expects($timeMatcher)
            ->method('getMicroTime')
            ->willReturnCallback(fn () => match ($timeMatcher->getInvocationCount()) {
                1 => 1695218778.6387,
                2 => 1695218796.3069,
            });

        $logMatcher = self::exactly(7);
        $this->logger
            ->expects($logMatcher)
            ->method('info')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'started'),
                2 => $this->assertEquals($message, 'created'),
                3 => $this->assertEquals($message, 'skipped'),
                4 => $this->assertEquals($message, 'organization:created'),
                5 => $this->assertEquals($message, 'created'),
                6 => $this->assertEquals($message, 'skipped'),
                7 => (
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
        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSource')
            ->with('bacidf')
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
            $this->regulationOrderRecordRepository,
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

    public function testExecuteImportFailed(): void
    {
        $organization = $this->createMock(Organization::class);

        $executor = new BacIdfExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
        );

        $record1 = ['ARR_REF' => 'arr_1'];
        $importCommand1 = $this->createMock(ImportBacIdfRegulationCommand::class);
        $importCommand1->generalInfoCommand = $this->createMock(SaveRegulationGeneralInfoCommand::class);
        $result1 = new BacIdfTransformerResult($importCommand1, [], $organization);

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

    public function testException(): void
    {
        $exc = new \Exception('Oops');
        $this->expectExceptionObject($exc);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSource')
            ->willThrowException($exc);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                ['started', []],
                [
                    'done', [
                        'numProcessed' => 0,
                        'numCreated' => 0,
                        'percentCreated' => 0,
                        'numSkipped' => 0,
                        'numSkippedNotCirculation' => 0,
                        'percentSkipped' => 0,
                        'numErrors' => 0,
                        'percentErrors' => 0,
                        'elapsedSeconds' => 0,
                    ],
                ],
            );

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('exception', ['exc' => $exc]);

        $executor = new BacIdfExecutor(
            $this->logger,
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
        );

        $executor->execute();
    }
}
