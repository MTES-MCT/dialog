<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Issue;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Issue\SaveRegulationOrderIssueCommand;
use App\Application\Regulation\Command\Issue\SaveRegulationOrderIssueCommandHandler;
use App\Domain\Regulation\Enum\RegulationOrderIssueLevelEnum;
use App\Domain\Regulation\RegulationOrderIssue;
use App\Domain\Regulation\Repository\RegulationOrderIssueRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveRegulationOrderIssueCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $regulationOrderIssueRepository;
    private MockObject $dateUtils;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderIssueRepository = $this->createMock(RegulationOrderIssueRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testCreate(): void
    {
        $createdAt = new \DateTimeImmutable('2023-05-22');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($createdAt);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $organization = $this->createMock(Organization::class);
        $createdRegulationOrderIssue = $this->createMock(RegulationOrderIssue::class);
        $createdRegulationOrderIssue
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->regulationOrderIssueRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new RegulationOrderIssue(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        identifier: 'F01/2024',
                        organization: $organization,
                        source: 'eudonet',
                        level: RegulationOrderIssueLevelEnum::WARNING->value,
                        context: 'Parsing des dates',
                        geometry: null,
                        createdAt: $createdAt,
                    ),
                ),
            )
            ->willReturn($createdRegulationOrderIssue);

        $handler = new SaveRegulationOrderIssueCommandHandler(
            $this->idFactory,
            $this->regulationOrderIssueRepository,
            $this->dateUtils,
        );

        $command = new SaveRegulationOrderIssueCommand(
            identifier: 'F01/2024',
            level: RegulationOrderIssueLevelEnum::WARNING->value,
            source: 'eudonet',
            context: 'Parsing des dates',
            organization: $organization,
            geometry: null,
        );
        $result = $handler($command);

        $this->assertSame('7fb74c5d-069b-4027-b994-7545bb0942d0', $result);
    }
}
