<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommandHandler;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Application\User\View\OrganizationUserView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetOrCreateOrganizationBySiretCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $organizationRepository;
    private MockObject $organizationUserRepository;
    private MockObject $dateUtils;
    private MockObject $organizationFetcher;
    private MockObject $commandBus;
    private GetOrCreateOrganizationBySiretCommandHandler $handler;
    private GetOrCreateOrganizationBySiretCommand $command;
    private string $siret = '82050375300015';

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->organizationFetcher = $this->createMock(ApiOrganizationFetcherInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->handler = new GetOrCreateOrganizationBySiretCommandHandler(
            $this->idFactory,
            $this->organizationRepository,
            $this->organizationUserRepository,
            $this->dateUtils,
            $this->organizationFetcher,
            $this->commandBus,
        );

        $this->command = new GetOrCreateOrganizationBySiretCommand($this->siret);
    }

    public function testReturnsExistingOrganization(): void
    {
        $existingOrganization = (new Organization('32516746-4fce-4750-ba83-7ae9b4290678'))
            ->setName('Fairness')
            ->setSiret($this->siret);

        $this->organizationRepository
            ->expects($this->once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn($existingOrganization);

        $this->organizationFetcher
            ->expects($this->never())
            ->method('findBySiret');

        $this->organizationRepository
            ->expects($this->never())
            ->method('add');

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->commandBus
            ->expects($this->never())
            ->method('dispatchAsync');

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findByOrganizationUuid')
            ->with($existingOrganization->getUuid())
            ->willReturn([$this->createMock(OrganizationUserView::class)]);

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(GetOrCreateOrganizationView::class, $result);
        $this->assertSame($existingOrganization, $result->organization);
        $this->assertTrue($result->hasOrganizationUsers);
    }

    public function testCreatesNewOrganization(): void
    {
        $now = new \DateTimeImmutable('2024-05-07');
        $orgUuid = 'd145a0e3-e397-412c-ba6a-90b150f7aec2';
        $orgName = 'Comune de Saint Ouen';
        $orgCode = '22930008201453';
        $orgCodeType = OrganizationCodeTypeEnum::INSEE->value;
        $departmentName = 'Seine-Saint-Denis';
        $departmentCode = '93';

        $this->organizationRepository
            ->expects($this->once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn(null);

        $organizationFetchedView = new OrganizationFetchedView(
            name: $orgName,
            code: $orgCode,
            codeType: $orgCodeType,
            departmentName: $departmentName,
            departmentCode: $departmentCode,
        );

        $this->organizationFetcher
            ->expects($this->once())
            ->method('findBySiret')
            ->with($this->siret)
            ->willReturn($organizationFetchedView);

        $this->dateUtils
            ->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $this->idFactory
            ->expects($this->once())
            ->method('make')
            ->willReturn($orgUuid);

        $expectedOrganization = (new Organization($orgUuid))
            ->setCreatedAt($now)
            ->setSiret($this->siret)
            ->setName($orgName)
            ->setCode($orgCode)
            ->setCodeType($orgCodeType)
            ->setDepartmentName($departmentName)
            ->setDepartmentCode($departmentCode);

        $this->organizationRepository
            ->expects($this->once())
            ->method('add')
            ->with($expectedOrganization);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatchAsync')
            ->with($this->callback(function ($command) use ($orgUuid) {
                return $command->organizationUuid === $orgUuid;
            }));

        $this->organizationUserRepository
            ->expects($this->once())
            ->method('findByOrganizationUuid')
            ->with($orgUuid)
            ->willReturn([]);

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(GetOrCreateOrganizationView::class, $result);
        $this->assertEquals($expectedOrganization, $result->organization);
        $this->assertFalse($result->hasOrganizationUsers);
    }

    public function testThrowsExceptionWhenOrganizationNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->organizationRepository
            ->expects($this->once())
            ->method('findOneBySiret')
            ->with($this->siret)
            ->willReturn(null);

        $exception = new OrganizationNotFoundException();
        $this->organizationFetcher
            ->expects($this->once())
            ->method('findBySiret')
            ->with($this->siret)
            ->willThrowException($exception);

        $this->dateUtils
            ->expects($this->once())
            ->method('getNow');

        $this->idFactory
            ->expects($this->never())
            ->method('make');

        $this->organizationRepository
            ->expects($this->never())
            ->method('add');

        $this->commandBus
            ->expects($this->never())
            ->method('dispatchAsync');

        $this->organizationUserRepository
            ->expects($this->never())
            ->method('findByOrganizationUuid');

        ($this->handler)($this->command);
    }
}
