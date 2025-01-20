<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommand;
use App\Application\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Litteralis\DTO\LitteralisCredentials;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\IntegrationReport\ReportFormatter;
use App\Infrastructure\Litteralis\LitteralisExecutor;
use App\Infrastructure\Litteralis\LitteralisExtractor;
use App\Infrastructure\Litteralis\LitteralisRecordEnum;
use App\Infrastructure\Litteralis\LitteralisTransformer;
use PHPUnit\Framework\TestCase;

final class LitteralisExecutorTest extends TestCase
{
    private $enabledOrgs;
    private $credentials;
    private $commandBus;
    private $queryBus;
    private $extractor;
    private $transformer;
    private $reporter;
    private $reportFormatter;
    private $dateUtils;
    private $orgId = '066b4d97-016e-77f9-8000-1e8dfaaba586';

    protected function setUp(): void
    {
        $this->enabledOrgs = ['test'];
        $this->credentials = (new LitteralisCredentials())
            ->add('test', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'testpassword');
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->extractor = $this->createMock(LitteralisExtractor::class);
        $this->transformer = $this->createMock(LitteralisTransformer::class);
        $this->reporter = $this->createMock(Reporter::class);
        $this->reportFormatter = $this->createMock(ReportFormatter::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testExecuteOrganizationNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organization test not found with orgId="066b4d97-016e-77f9-8000-1e8dfaaba586"');

        $laterThan = new \DateTimeImmutable('2024-08-01');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willThrowException(new OrganizationNotFoundException());

        $this->extractor
            ->expects(self::never())
            ->method('extractFeaturesByRegulation');
        $this->transformer
            ->expects(self::never())
            ->method('transform');

        $executor = new LitteralisExecutor(
            $this->enabledOrgs,
            $this->credentials,
            $this->commandBus,
            $this->queryBus,
            $this->extractor,
            $this->transformer,
            $this->reportFormatter,
            $this->dateUtils,
        );

        $executor->execute('test', $this->orgId, $laterThan, $this->reporter);
    }

    public function testExecute(): void
    {
        $laterThan = new \DateTimeImmutable('2024-08-01');
        $organizationId = '066bcaff-23b8-7745-8000-d296434f2a8a';

        $organization = $this->createMock(Organization::class);
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

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('getNow')
            ->willReturnOnConsecutiveCalls($startTime, $endTime);

        $this->reporter
            ->expects(self::once())
            ->method('start')
            ->with('test', $startTime, $organization);

        $features1 = ['feature1A', 'feature1B'];
        $features2 = ['feature2A'];
        $features3 = [
            [
                'type' => 'Feature',
                'properties' => [
                    'idemprise' => 'feature3A',
                    'arretesrcid' => '1234',
                    'shorturl' => 'https://dl.sogelink.fr/?n3omzTyS',
                ],
            ],
            [
                'type' => 'Feature',
                'properties' => [
                    'idemprise' => 'feature3B',
                    'arretesrcid' => '1234',
                    'shorturl' => 'https://dl.sogelink.fr/?n3omzTyS',
                ],
            ],
        ];

        $this->extractor
            ->expects(self::once())
            ->method('extractFeaturesByRegulation')
            ->with('test', $laterThan, $this->reporter)
            ->willReturn(
                [
                    'identifier1' => $features1,
                    'identifier2' => $features2,
                    'identifier3' => $features3,
                ],
            );

        $this->transformer
            ->expects(self::exactly(3))
            ->method('transform')
            ->withConsecutive(
                [$this->reporter, 'identifier1', $features1, $organization], // Success
                [$this->reporter, 'identifier2', $features2, $organization], // Transformation error
                [$this->reporter, 'identifier3', $features3, $organization], // Command execution error
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

        $this->reporter
            ->expects(self::once())
            ->method('addError')
            ->with(LitteralisRecordEnum::ERROR_IMPORT_COMMAND_FAILED->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => '1234',
                CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                CommonRecordEnum::ATTR_DETAILS->value => [
                    'message' => 'oops',
                ],
                'violations' => null,
                'command' => $command3,
            ]);

        $this->reporter
            ->expects(self::exactly(3))
            ->method('acknowledgeNewErrors');

        $this->reporter
            ->expects(self::once())
            ->method('end')
            ->with($endTime);

        $this->reporter
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn(['record1', 'record2', '...']);

        $this->reportFormatter
            ->expects(self::once())
            ->method('format')
            ->with(['record1', 'record2', '...'])
            ->willReturn('report');

        $this->reporter
            ->expects(self::once())
            ->method('onReport')
            ->with('report');

        $executor = new LitteralisExecutor(
            $this->enabledOrgs,
            $this->credentials,
            $this->commandBus,
            $this->queryBus,
            $this->extractor,
            $this->transformer,
            $this->reportFormatter,
            $this->dateUtils,
        );

        $this->assertSame('report', $executor->execute('test', $this->orgId, $laterThan, $this->reporter));
    }
}
