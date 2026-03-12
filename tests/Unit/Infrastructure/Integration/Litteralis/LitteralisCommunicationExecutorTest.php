<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommand;
use App\Application\Integration\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\IntegrationReport\ReportFormatter;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExecutor;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationExtractor;
use App\Infrastructure\Integration\Litteralis\LitteralisTransformer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LitteralisCommunicationExecutorTest extends TestCase
{
    private array $enabledOrgs;
    private LitteralisCredentials $credentials;
    private $commandBus;
    private $queryBus;
    private $entityManager;
    private $extractor;
    private $transformer;
    private $reporter;
    private $reportFormatter;
    private $dateUtils;
    private string $orgId = '066b4d97-016e-77f9-8000-1e8dfaaba586';

    protected function setUp(): void
    {
        $this->enabledOrgs = ['test'];
        $this->credentials = (new LitteralisCredentials())
            ->add('test', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'testpassword');
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->extractor = $this->createMock(LitteralisCommunicationExtractor::class);
        $this->transformer = $this->createMock(LitteralisTransformer::class);
        $this->reporter = $this->createMock(Reporter::class);
        $this->reportFormatter = $this->createMock(ReportFormatter::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    private function createExecutor(): LitteralisCommunicationExecutor
    {
        return new LitteralisCommunicationExecutor(
            $this->enabledOrgs,
            $this->credentials,
            $this->commandBus,
            $this->queryBus,
            $this->entityManager,
            $this->extractor,
            $this->transformer,
            $this->reportFormatter,
            $this->dateUtils,
        );
    }

    public function testExecuteThrowsWhenOrganizationNotFound(): void
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

        $this->createExecutor()->execute('test', $this->orgId, $laterThan, $this->reporter);
    }

    public function testExecuteSuccessCommitsTransactionAndReturnsReport(): void
    {
        $laterThan = new \DateTimeImmutable('2024-08-01');
        $organizationId = '066bcaff-23b8-7745-8000-d296434f2a8a';

        $organization = $this->createMock(Organization::class);
        $command1 = $this->createMock(ImportLitteralisRegulationCommand::class);
        $command2 = $this->createMock(ImportLitteralisRegulationCommand::class);

        $startTime = new \DateTimeImmutable('2024-08-01 10:00:00');
        $endTime = new \DateTimeImmutable('2024-08-01 10:01:32');
        $cleanUpCommand = new CleanUpLitteralisRegulationsBeforeImportCommand($organizationId, $laterThan);

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $this->entityManager->method('getConnection')->willReturn($connection);

        $organization->method('getUuid')->willReturn($organizationId);
        $this->queryBus->method('handle')->with(new GetOrganizationByUuidQuery($this->orgId))->willReturn($organization);
        $this->dateUtils->method('getNow')->willReturnOnConsecutiveCalls($startTime, $endTime);
        $this->reporter->method('start')->with('test', $startTime, $organization);

        $features1 = ['feature1A'];
        $features2 = ['feature2A'];
        $this->extractor->method('extractFeaturesByRegulation')->willReturn([
            'identifier1' => $features1,
            'identifier2' => $features2,
        ]);
        $this->transformer->method('transform')->willReturnOnConsecutiveCalls($command1, $command2);

        $this->commandBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive([$cleanUpCommand], [$command1], [$command2]);

        $this->reporter->method('acknowledgeNewErrors');
        $this->reporter->method('end')->with($endTime);
        $this->reporter->method('getRecords')->willReturn(['record1', 'record2']);
        $this->reportFormatter->method('format')->with(['record1', 'record2'])->willReturn('Communication report');
        $this->reporter->method('onReport')->with('Communication report');

        $result = $this->createExecutor()->execute('test', $this->orgId, $laterThan, $this->reporter);

        $this->assertSame('Communication report', $result);
    }
}
