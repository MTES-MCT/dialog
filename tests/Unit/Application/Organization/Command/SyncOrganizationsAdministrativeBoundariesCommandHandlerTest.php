<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\Organization\Command\SyncOrganizationsAdministrativeBoundariesCommand;
use App\Application\Organization\Command\SyncOrganizationsAdministrativeBoundariesCommandHandler;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncOrganizationsAdministrativeBoundariesCommandHandlerTest extends TestCase
{
    private MockObject $organizationRepository;
    private MockObject $apiOrganizationFetcher;
    private MockObject $dateUtils;
    private SyncOrganizationsAdministrativeBoundariesCommandHandler $handler;

    protected function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->apiOrganizationFetcher = $this->createMock(ApiOrganizationFetcherInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);

        $this->handler = new SyncOrganizationsAdministrativeBoundariesCommandHandler(
            $this->organizationRepository,
            $this->apiOrganizationFetcher,
            $this->dateUtils,
        );
    }

    public function testHandleSuccessfully(): void
    {
        $now = new \DateTimeImmutable();
        $geometry = '{"type":"Polygon","coordinates":[[[-1.908121,47.283032],[-1.979851,47.293559],[-2.00338,47.301395],[-2.007112,47.300037],[-2.007475,47.296932],[-2.010651,47.297796],[-2.010856,47.313925],[-2.005861,47.314136],[-2.004003,47.316117],[-2.006623,47.317527],[-2.004864,47.319338],[-2.005968,47.321869],[-2.003621,47.331363],[-2.004887,47.334062],[-2.008749,47.335486],[-2.011075,47.34475],[-2.059966,47.356433],[-2.0525,47.36184],[-2.040569,47.365045],[-2.039125,47.370852],[-2.033239,47.377244],[-2.042033,47.380824],[-2.042396,47.382887],[-2.028198,47.395458],[-2.042507,47.398949],[-2.034801,47.404832],[-2.032553,47.41069],[-2.028522,47.415627],[-2.016471,47.410135],[-2.008568,47.402602],[-1.994444,47.393943],[-1.994516,47.392499],[-1.978868,47.386098],[-1.972438,47.388033],[-1.973753,47.389075],[-1.971607,47.392301],[-1.967789,47.393216],[-1.964392,47.396957],[-1.960599,47.3979],[-1.956507,47.395079],[-1.952263,47.396254],[-1.951244,47.390677],[-1.946721,47.383328],[-1.947754,47.377566],[-1.925809,47.373885],[-1.916435,47.380708],[-1.903132,47.376632],[-1.897404,47.379754],[-1.890707,47.380244],[-1.882303,47.378515],[-1.880128,47.373167],[-1.864686,47.378226],[-1.856858,47.376705],[-1.850944,47.378082],[-1.844229,47.3822],[-1.838952,47.381875],[-1.840011,47.3767],[-1.836476,47.369967],[-1.838982,47.368279],[-1.840724,47.362347],[-1.829137,47.356124],[-1.815556,47.345277],[-1.794504,47.332174],[-1.797999,47.330475],[-1.81212,47.333957],[-1.820959,47.331729],[-1.82696,47.328421],[-1.830413,47.323731],[-1.835777,47.322116],[-1.843361,47.316784],[-1.846813,47.312947],[-1.852112,47.313982],[-1.85755,47.312579],[-1.871023,47.308263],[-1.870762,47.305765],[-1.875182,47.307436],[-1.8806,47.294985],[-1.891649,47.283782],[-1.899088,47.279914],[-1.908121,47.283032]]]}';

        $organization2 = $this->createMock(Organization::class);
        $organization2->expects($this->once())
            ->method('getSiret')
            ->willReturn('22930008201453');

        $organization = $this->createMock(Organization::class);
        $organization->expects($this->once())
            ->method('getSiret')
            ->willReturn('21440195200129');

        $organization->expects($this->once())
            ->method('setName')
            ->with('COMMUNE DE SAVENAY')
            ->willReturnSelf();

        $organization->expects($this->once())
            ->method('setGeometry')
            ->with($geometry)
            ->willReturnSelf();

        $organization->expects($this->once())
            ->method('setCode')
            ->with('44260')
            ->willReturnSelf();

        $organization->expects($this->once())
            ->method('setCodeType')
            ->with('insee')
            ->willReturnSelf();

        $organization->expects($this->once())
            ->method('setUpdatedAt')
            ->with($now)
            ->willReturnSelf();

        $organizationView = new OrganizationFetchedView(
            name: 'COMMUNE DE SAVENAY',
            code: '44260',
            codeType: 'insee',
            geometry: $geometry,
        );

        $organizationView2 = new OrganizationFetchedView(
            name: 'DEPARTEMENT DE LA SEINE SAINT DENIS',
            code: '93',
            codeType: 'department',
            geometry: null,
        );

        $this->organizationRepository
            ->expects($this->once())
            ->method('findAllEntities')
            ->willReturn([$organization, $organization2]);

        $this->apiOrganizationFetcher
            ->expects($this->exactly(2))
            ->method('findBySiret')
            ->withConsecutive(
                ['21440195200129'],
                ['22930008201453'],
            )
            ->willReturnOnConsecutiveCalls(
                $organizationView,
                $organizationView2,
            );

        $this->dateUtils
            ->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $result = ($this->handler)(new SyncOrganizationsAdministrativeBoundariesCommand());

        $this->assertSame(2, $result->totalOrganizations);
        $this->assertSame(1, $result->updatedOrganizations);
    }
}
