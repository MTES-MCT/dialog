<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Geography;

use App\Application\AdministrativeBoundaryFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\AdministrativeBoundaryResolver;
use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Application\Geography\View\FetchedAdministrativeBoundaryView;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AdministrativeBoundaryResolverTest extends TestCase
{
    private const GEOMETRY = '{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1],[0,0]]]}';

    private AdministrativeBoundaryRepositoryInterface&MockObject $repository;
    private AdministrativeBoundaryFetcherInterface&MockObject $fetcher;
    private \DateTimeImmutable $now;
    private AdministrativeBoundaryResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AdministrativeBoundaryRepositoryInterface::class);
        $this->fetcher = $this->createMock(AdministrativeBoundaryFetcherInterface::class);
        $this->now = new \DateTimeImmutable('2026-09-30');
        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils->method('getNow')->willReturn($this->now);

        $this->resolver = new AdministrativeBoundaryResolver($this->repository, $this->fetcher, $dateUtils);
    }

    public function testResolveReturnsStoredBoundaryWithoutFetching(): void
    {
        $stored = new AdministrativeBoundaryView('departement', '44', 'Loire-Atlantique');

        $this->repository
            ->expects(self::once())
            ->method('findOneByCode')
            ->with('departement', '44')
            ->willReturn($stored);
        $this->fetcher->expects(self::never())->method('fetch');
        $this->repository->expects(self::never())->method('save');

        $this->assertSame($stored, $this->resolver->resolve(OrganizationCodeTypeEnum::DEPARTMENT, '44'));
    }

    public function testResolveFetchesAndStoresUnknownBoundary(): void
    {
        $this->repository->method('findOneByCode')->willReturn(null);
        $this->fetcher
            ->expects(self::once())
            ->method('fetch')
            ->with(OrganizationCodeTypeEnum::EPCI, '244400404')
            ->willReturn(new FetchedAdministrativeBoundaryView('Nantes Métropole', self::GEOMETRY));
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with('epci', '244400404', 'Nantes Métropole', self::GEOMETRY, $this->now);

        $this->assertEquals(
            new AdministrativeBoundaryView('epci', '244400404', 'Nantes Métropole'),
            $this->resolver->resolve(OrganizationCodeTypeEnum::EPCI, '244400404'),
        );
    }

    public function testResolveNormalizesCode(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findOneByCode')
            ->with('departement', '2A')
            ->willReturn(new AdministrativeBoundaryView('departement', '2A', 'Corse-du-Sud'));

        $this->assertSame('2A', $this->resolver->resolve(OrganizationCodeTypeEnum::DEPARTMENT, ' 2a ')->code);
    }

    /**
     * @dataProvider provideMalformedCodes
     */
    public function testResolveRejectsMalformedCodeWithoutFetching(OrganizationCodeTypeEnum $codeType, string $code): void
    {
        $this->repository->expects(self::never())->method('findOneByCode');
        $this->fetcher->expects(self::never())->method('fetch');

        try {
            $this->resolver->resolve($codeType, $code);
            $this->fail('Expected an AdministrativeBoundaryNotFoundException');
        } catch (AdministrativeBoundaryNotFoundException $exc) {
            $this->assertSame($codeType->value, $exc->getCodeType());
            $this->assertSame($code, $exc->getBoundaryCode());
            $this->assertSame(
                \sprintf('No administrative boundary found for code "%s" of type "%s"', $code, $codeType->value),
                $exc->getMessage(),
            );
        }
    }

    public function provideMalformedCodes(): array
    {
        return [
            'commune trop courte' => [OrganizationCodeTypeEnum::INSEE, '4410'],
            'commune avec lettres' => [OrganizationCodeTypeEnum::INSEE, 'ABCDE'],
            'département à une lettre' => [OrganizationCodeTypeEnum::DEPARTMENT, '2C'],
            'département trop long' => [OrganizationCodeTypeEnum::DEPARTMENT, '9741'],
            'tentative d\'injection CQL' => [OrganizationCodeTypeEnum::DEPARTMENT, "44' OR '1'='1"],
            'région à trois chiffres' => [OrganizationCodeTypeEnum::REGION, '520'],
            'EPCI trop court' => [OrganizationCodeTypeEnum::EPCI, '2444004'],
            'code vide' => [OrganizationCodeTypeEnum::REGION, ''],
        ];
    }

    /**
     * @dataProvider provideWellFormedCodes
     */
    public function testResolveAcceptsWellFormedCodes(OrganizationCodeTypeEnum $codeType, string $code): void
    {
        $this->repository->method('findOneByCode')->willReturn(new AdministrativeBoundaryView($codeType->value, $code, 'Nom'));

        $this->assertSame($code, $this->resolver->resolve($codeType, $code)->code);
    }

    public function provideWellFormedCodes(): array
    {
        return [
            'commune' => [OrganizationCodeTypeEnum::INSEE, '44109'],
            'commune corse' => [OrganizationCodeTypeEnum::INSEE, '2A004'],
            'département' => [OrganizationCodeTypeEnum::DEPARTMENT, '08'],
            'département corse' => [OrganizationCodeTypeEnum::DEPARTMENT, '2B'],
            'département d\'outre-mer' => [OrganizationCodeTypeEnum::DEPARTMENT, '974'],
            'région' => [OrganizationCodeTypeEnum::REGION, '52'],
            'EPCI' => [OrganizationCodeTypeEnum::EPCI, '244400404'],
        ];
    }

    public function testResolvePropagatesUnavailability(): void
    {
        $this->expectException(AdministrativeBoundaryUnavailableException::class);

        $this->repository->method('findOneByCode')->willReturn(null);
        $this->fetcher->method('fetch')->willThrowException(new AdministrativeBoundaryUnavailableException('WFS down'));
        $this->repository->expects(self::never())->method('save');

        $this->resolver->resolve(OrganizationCodeTypeEnum::REGION, '52');
    }

    public function testResolveCodesIgnoresMissingCodes(): void
    {
        $this->repository
            ->method('findOneByCode')
            ->willReturnCallback(static fn (string $codeType, string $code) => new AdministrativeBoundaryView($codeType, $code, 'Nom'));

        $this->assertSame(
            ['departement' => '2B', 'region' => '52'],
            $this->resolver->resolveCodes([
                'departement' => '2b',
                'epci' => null,
                'region' => '52',
                'insee' => '',
            ]),
        );
        $this->assertSame([], $this->resolver->resolveCodes([]));
    }

    public function testRefreshAlwaysFetchesAndStores(): void
    {
        $this->repository->expects(self::never())->method('findOneByCode');
        $this->fetcher
            ->expects(self::once())
            ->method('fetch')
            ->with(OrganizationCodeTypeEnum::REGION, '52')
            ->willReturn(new FetchedAdministrativeBoundaryView('Pays de la Loire', self::GEOMETRY));
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with('region', '52', 'Pays de la Loire', self::GEOMETRY, $this->now);

        $this->assertEquals(
            new AdministrativeBoundaryView('region', '52', 'Pays de la Loire'),
            $this->resolver->refresh(OrganizationCodeTypeEnum::REGION, '52'),
        );
    }
}
