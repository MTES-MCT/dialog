<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\JOP;

use App\Application\CommandBusInterface;
use App\Application\JOP\Command\ImportJOPRegulationCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\JOP\JOPExecutor;
use App\Infrastructure\JOP\JOPExtractor;
use App\Infrastructure\JOP\JOPTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JOPExecutorTest extends TestCase
{
    private $logger;
    private $commandBus;
    private $queryBus;
    private $regulationOrderRecordRepository;
    private $extractor;
    private $transformer;
    private $orgId = '0667d658-d3aa-7d14-8000-b45149ab9f2d';

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->extractor = $this->createMock(JOPExtractor::class);
        $this->transformer = $this->createMock(JOPTransformer::class);
    }

    public function testExecuteOrgIdMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Please set APP_JOP_ORG_ID/');

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findOneUuidByIdentifierInOrganization');

        $this->extractor
            ->expects(self::never())
            ->method('extractGeoJSON');

        $this->logger
            ->expects(self::never())
            ->method('info');

        $executor = new JOPExecutor(
            $this->logger,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->extractor,
            $this->transformer,
            jopOrgId: '',
        );

        $executor->execute();
    }

    public function testExecuteOrganizationNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Organization not found/');

        $this->extractor
            ->expects(self::never())
            ->method('extractGeoJSON');

        $this->transformer
            ->expects(self::never())
            ->method('transform');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('findOneUuidByIdentifierInOrganization');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willThrowException(new OrganizationNotFoundException());

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $executor = new JOPExecutor(
            $this->logger,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->extractor,
            $this->transformer,
            jopOrgId: $this->orgId,
        );

        $executor->execute();
    }

    public function testExecute(): void
    {
        $organization = $this->createMock(Organization::class);
        $existingRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = $this->createMock(ImportJOPRegulationCommand::class);
        $geoJSON = ['features' => ['...']];

        $this->extractor
            ->expects(self::once())
            ->method('extractGeoJSON')
            ->willReturn($geoJSON);

        $this->transformer
            ->expects(self::once())
            ->method('transform')
            ->with($geoJSON, $organization)
            ->willReturn($command);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneUuidByIdentifierInOrganization')
            ->with('JOP2024-ZONES', $organization)
            ->willReturn('064ef37c-170f-7737-8000-d6f3b1db7685');

        $this->queryBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive(
                [new GetOrganizationByUuidQuery($this->orgId)],
                [new GetRegulationOrderRecordByUuidQuery('064ef37c-170f-7737-8000-d6f3b1db7685')],
            )
            ->willReturnOnConsecutiveCalls(
                $organization,
                $existingRegulationOrderRecord,
            );

        $this->commandBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive(
                [new DeleteRegulationCommand([$this->orgId], $existingRegulationOrderRecord)],
                [$command],
            );

        $executor = new JOPExecutor(
            $this->logger,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->extractor,
            $this->transformer,
            jopOrgId: $this->orgId,
        );

        $executor->execute();
    }

    public function testExecutImoprtFailed(): void
    {
        $this->expectException(\RuntimeException::class);

        $organization = $this->createMock(Organization::class);
        $existingRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = $this->createMock(ImportJOPRegulationCommand::class);
        $geoJSON = ['features' => ['...']];

        $this->extractor
            ->expects(self::once())
            ->method('extractGeoJSON')
            ->willReturn($geoJSON);

        $this->transformer
            ->expects(self::once())
            ->method('transform')
            ->with($geoJSON, $organization)
            ->willReturn($command);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneUuidByIdentifierInOrganization')
            ->with('JOP2024-ZONES', $organization)
            ->willReturn(null);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willThrowException(new \RuntimeException('oops'));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('import:error', ['message' => 'oops', 'violations' => null]);

        $executor = new JOPExecutor(
            $this->logger,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->extractor,
            $this->transformer,
            jopOrgId: $this->orgId,
        );

        $executor->execute();
    }
}
