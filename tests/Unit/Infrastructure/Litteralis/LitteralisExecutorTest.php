<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommand;
use App\Application\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\Litteralis\LitteralisExecutor;
use App\Infrastructure\Litteralis\LitteralisExtractor;
use App\Infrastructure\Litteralis\LitteralisReporter;
use App\Infrastructure\Litteralis\LitteralisReporterFactory;
use App\Infrastructure\Litteralis\LitteralisReportFormatter;
use App\Infrastructure\Litteralis\LitteralisTransformer;
use PHPUnit\Framework\TestCase;

final class LitteralisExecutorTest extends TestCase
{
    private $commandBus;
    private $queryBus;
    private $extractor;
    private $transformer;
    private $reporterFactory;
    private $reportFormatter;
    private $dateUtils;
    private $orgId = '066b4d97-016e-77f9-8000-1e8dfaaba586';

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->extractor = $this->createMock(LitteralisExtractor::class);
        $this->transformer = $this->createMock(LitteralisTransformer::class);
        $this->reporterFactory = $this->createMock(LitteralisReporterFactory::class);
        $this->reportFormatter = $this->createMock(LitteralisReportFormatter::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecuteOrganizationNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Organization not found/');

        $laterThan = new \DateTimeImmutable('2024-08-01');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willThrowException(new OrganizationNotFoundException());

        $this->reporterFactory
            ->expects(self::never())
            ->method('createReporter');
        $this->extractor
            ->expects(self::never())
            ->method('extractFeaturesByRegulation');
        $this->transformer
            ->expects(self::never())
            ->method('transform');

        $executor = new LitteralisExecutor(
            $this->commandBus,
            $this->queryBus,
            $this->extractor,
            $this->transformer,
            $this->reporterFactory,
            $this->reportFormatter,
            $this->dateUtils,
        );

        $executor->execute($this->orgId, $laterThan);
    }

    public function testExecute(): void
    {
        $laterThan = new \DateTimeImmutable('2024-08-01');
        $organizationId = '066bcaff-23b8-7745-8000-d296434f2a8a';

        $organization = $this->createMock(Organization::class);
        $reporter = $this->createMock(LitteralisReporter::class);
        $command1 = $this->createMock(ImportLitteralisRegulationCommand::class);
        $command3 = $this->createMock(ImportLitteralisRegulationCommand::class);

        $startTime = new \DateTimeImmutable('2024-08-01 10:00:00');
        $endTime = new \DateTimeImmutable('2024-08-01 10:01:32');

        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($organizationId);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->reporterFactory
            ->expects(self::once())
            ->method('createReporter')
            ->willReturn($reporter);

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('getNow')
            ->willReturnOnConsecutiveCalls($startTime, $endTime);

        $reporter
            ->expects(self::once())
            ->method('start')
            ->with($startTime, $organization);

        $this->extractor
            ->expects(self::once())
            ->method('extractFeaturesByRegulation')
            ->with($laterThan, $reporter)
            ->willReturn(
                [
                    'identifier1' => ['feature1A', 'feature1B'],
                    'identifier2' => ['feature2A'],
                    'identifier3' => [
                        [
                            'type' => 'Feature',
                            'properties' => [
                                'idemprise' => 'feature3A',
                                'arretesrcid' => '1234',
                            ],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => [
                                'idemprise' => 'feature3B',
                                'arretesrcid' => '1234',
                            ],
                        ],
                    ],
                ],
            );

        $this->transformer
            ->expects(self::exactly(3))
            ->method('transform')
            ->withConsecutive(
                [$reporter, 'identifier1', ['feature1A', 'feature1B'], $organization], // Success
                [$reporter, 'identifier2', ['feature2A'], $organization], // Transformation error
                [
                    $reporter,
                    'identifier3',
                    [
                        [
                            'type' => 'Feature',
                            'properties' => [
                                'idemprise' => 'feature3A',
                                'arretesrcid' => '1234',
                            ],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => [
                                'idemprise' => 'feature3B',
                                'arretesrcid' => '1234',
                            ],
                        ],
                    ],
                    $organization,
                ], // Command execution error
            )
            ->willReturnOnConsecutiveCalls($command1, null, $command3);

        $cleanUpCommand = new CleanUpLitteralisRegulationsBeforeImportCommand($organizationId, $laterThan);

        $matcher = self::exactly(3);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->withConsecutive([$cleanUpCommand], [$command1], [$command3])
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($cleanUpCommand, $command) ?: null,
                    2 => $this->assertEquals($command1, $command) ?: null,
                    3 => $this->assertEquals($command3, $command) ?: throw new \RuntimeException('oops'),
                },
            );

        $reporter
            ->expects(self::once())
            ->method('addError')
            ->with($reporter::ERROR_IMPORT_COMMAND_FAILED, [
                'message' => 'oops',
                'arretesrcid' => '1234',
                'violations' => null,
                'command' => $command3,
            ]);

        $reporter
            ->expects(self::exactly(3))
            ->method('acknowledgeNewErrors');

        $reporter
            ->expects(self::once())
            ->method('end')
            ->with($endTime);

        $reporter
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn(['record1', 'record2', '...']);

        $this->reportFormatter
            ->expects(self::once())
            ->method('format')
            ->with(['record1', 'record2', '...'])
            ->willReturn('report');

        $reporter
            ->expects(self::once())
            ->method('onReport')
            ->with('report');

        $executor = new LitteralisExecutor(
            $this->commandBus,
            $this->queryBus,
            $this->extractor,
            $this->transformer,
            $this->reporterFactory,
            $this->reportFormatter,
            $this->dateUtils,
        );

        $this->assertSame('report', $executor->execute($this->orgId, $laterThan));
    }
}
