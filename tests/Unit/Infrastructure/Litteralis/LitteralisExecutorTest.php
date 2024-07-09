<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
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

        $executor->execute($this->orgId);
    }

    public function testExecute(): void
    {
        $organization = $this->createMock(Organization::class);
        $reporter = $this->createMock(LitteralisReporter::class);
        $command1 = $this->createMock(ImportLitteralisRegulationCommand::class);
        $command3 = $this->createMock(ImportLitteralisRegulationCommand::class);

        $startTime = new \DateTimeImmutable('2024-08-01 10:00:00');
        $endTime = new \DateTimeImmutable('2024-08-01 10:01:32');

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
            ->with($startTime);

        $this->extractor
            ->expects(self::once())
            ->method('extractFeaturesByRegulation')
            ->with($reporter)
            ->willReturn(
                [
                    ['feature1A', 'feature1B'],
                    ['feature2A'],
                    ['feature3A', 'feature3B'],
                ],
            );

        $this->transformer
            ->expects(self::exactly(3))
            ->method('transform')
            ->withConsecutive(
                [$reporter, ['feature1A', 'feature1B'], $organization], // Success
                [$reporter, ['feature2A'], $organization], // Transformation error
                [$reporter, ['feature3A', 'feature3B'], $organization], // Command execution error
            )
            ->willReturnOnConsecutiveCalls($command1, null, $command3);

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->withConsecutive([$command1], [$command3])
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($command1, $command) ?: null,
                    2 => $this->assertEquals($command3, $command) ?: throw new \RuntimeException('oops'),
                },
            );

        $reporter
            ->expects(self::once())
            ->method('addError')
            ->with($reporter::ERROR_IMPORT_COMMAND_FAILED, [
                'message' => 'oops',
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

        $executor = new LitteralisExecutor(
            $this->commandBus,
            $this->queryBus,
            $this->extractor,
            $this->transformer,
            $this->reporterFactory,
            $this->reportFormatter,
            $this->dateUtils,
        );

        $this->assertSame('report', $executor->execute($this->orgId));
    }
}
