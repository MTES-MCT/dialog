<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
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

final class EudonetParisExecutorTest extends TestCase
{
    private $extractor;
    private $transformer;
    private $commandBus;
    private $queryBus;
    private $regulationOrderRecordRepository;
    private $orgId = '064f5eba-5eb2-7ffd-8000-77e8f8b7bb9b';

    protected function setUp(): void
    {
        $this->extractor = $this->createMock(EudonetParisExtractor::class);
        $this->transformer = $this->createMock(EudonetParisTransformer::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
    }

    public function testExecute(): void
    {
        $now = new \DateTimeImmutable('now');
        $organization = $this->createMock(Organization::class);

        $executor = new EudonetParisExecutor(
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
        );

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetOrganizationByUuidQuery($this->orgId))
            ->willReturn($organization);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findIdentifiersForSourceInOrganization')
            ->willReturn(['064ef37c-170f-7737-8000-d6f3b1db7685']);

        $record1 = ['fields' => [1101 => '20210104']];
        $importCommand1 = $this->createMock(ImportEudonetParisRegulationCommand::class);
        $result1 = new EudonetParisTransformerResult($importCommand1, []);

        $record2 = ['fields' => [1101 => '20210305']];
        $result2 = new EudonetParisTransformerResult(null, ['something was wrong with record 2']);

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

        $report = $executor->execute($now);

        $this->assertFalse($report->hasError());
        $this->assertSame([
            'Processed: 3',
            'Created: 2 (66.7 %)',
            'Skipped: 1 (33.3 %)',
            'Messages:',
            'something was wrong with record 2',
            '',
        ], $report->getLines());
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
            ->method('findIdentifiersForSourceInOrganization')
            ->with('eudonet_paris', $organization)
            ->willReturn([]);

        $this->extractor
            ->expects(self::once())
            ->method('iterExtract')
            ->with($now, [])
            ->willReturn(new \EmptyIterator());

        $executor = new EudonetParisExecutor(
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
        );

        $report = $executor->execute($now);

        $this->assertFalse($report->hasError());
        $this->assertSame([
            'Processed: 0',
            'Created: 0 (0.0 %)',
            'Skipped: 0 (0.0 %)',
            'Messages:',
            '',
        ], $report->getLines());
    }

    public function testExecuteOrganizationDoesNotExist(): void
    {
        $now = new \DateTimeImmutable('now');

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

        $executor = new EudonetParisExecutor(
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            $this->orgId,
        );

        $report = $executor->execute($now);

        $this->assertTrue($report->hasError());
        [$line1, $line2] = $report->getLines();
        $this->assertStringStartsWith('ERROR: ', $line1);
        $this->assertStringContainsString('my_message', $line1);
        $this->assertSame('', $line2);
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
            ->method('findIdentifiersForSourceInOrganization');

        $this->extractor
            ->expects(self::never())
            ->method('iterExtract');

        $executor = new EudonetParisExecutor(
            $this->extractor,
            $this->transformer,
            $this->commandBus,
            $this->queryBus,
            $this->regulationOrderRecordRepository,
            '',
        );

        $executor->execute($now);
    }
}
