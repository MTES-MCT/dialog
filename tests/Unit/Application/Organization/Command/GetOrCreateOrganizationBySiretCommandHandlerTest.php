<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommandHandler;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetOrCreateOrganizationBySiretCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $organizationRepository;
    private MockObject $dateUtils;
    private MockObject $organizationFetcher;
    private GetOrCreateOrganizationBySiretCommandHandler $handler;
    private GetOrCreateOrganizationBySiretCommand $command;
    private string $siret = '82050375300015';

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->organizationFetcher = $this->createMock(ApiOrganizationFetcherInterface::class);

        $this->handler = new GetOrCreateOrganizationBySiretCommandHandler(
            $this->idFactory,
            $this->organizationRepository,
            $this->dateUtils,
            $this->organizationFetcher,
        );

        $this->command = new GetOrCreateOrganizationBySiretCommand($this->siret);
    }

    public function testReturnsExistingOrganization(): void
    {
        $existingOrganization = (new Organization('32516746-4fce-4750-ba83-7ae9b4290678'))
            ->setName('Fairness')
            ->setSiret($this->siret);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn($existingOrganization);

        $this->organizationFetcher
            ->expects(self::never())
            ->method('findBySiret');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(GetOrCreateOrganizationView::class, $result);
        $this->assertSame($existingOrganization, $result->organization);
        $this->assertFalse($result->isCreated);
    }

    public function testCreatesNewOrganization(): void
    {
        $now = new \DateTimeImmutable('2024-05-07');
        $orgUuid = 'd145a0e3-e397-412c-ba6a-90b150f7aec2';
        $orgName = 'Fairness';

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn(null);

        $this->organizationFetcher
            ->expects(self::once())
            ->method('findBySiret')
            ->with($this->siret)
            ->willReturn(['name' => $orgName]);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn($orgUuid);

        $expectedOrganization = (new Organization($orgUuid))
            ->setCreatedAt($now)
            ->setSiret($this->siret)
            ->setName($orgName);

        $this->organizationRepository
            ->expects(self::once())
            ->method('add')
            ->with($expectedOrganization);

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(GetOrCreateOrganizationView::class, $result);
        $this->assertEquals($expectedOrganization, $result->organization);
        $this->assertTrue($result->isCreated);
    }

    public function testThrowsExceptionWhenOrganizationNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn(null);

        $this->organizationFetcher
            ->expects(self::once())
            ->method('findBySiret')
            ->with($this->siret)
            ->willThrowException(new OrganizationNotFoundException());

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->organizationRepository
            ->expects(self::never())
            ->method('add');

        ($this->handler)($this->command);
    }
}
