<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use App\Infrastructure\Controller\Api\Regulations\GetRegulationController;
use App\Infrastructure\DTO\Regulation\RegulationApiView;
use App\Infrastructure\Security\User\ApiClientUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GetRegulationControllerTest extends TestCase
{
    private QueryBusInterface&MockObject $queryBus;
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;
    private NormalizerInterface&MockObject $normalizer;
    private GetRegulationController $controller;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);

        $this->controller = new GetRegulationController(
            $this->queryBus,
            $this->commandBus,
            $this->security,
            $this->normalizer,
        );
    }

    public function testGetRegulation(): void
    {
        $organization = $this->createMock(Organization::class);

        $user = $this->createMock(ApiClientUser::class);
        $user->method('getOrganization')->willReturn($organization);
        $user->method('getUserIdentifier')->willReturn('clientId');

        $this->security->method('getUser')->willReturn($user);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord->method('getUuid')->willReturn('roc-uuid');

        $generalInfo = new GeneralInfoView(
            uuid: 'roc-uuid',
            identifier: 'F2025/001',
            organizationName: 'Ma collectivité',
            organizationLogo: null,
            organizationUuid: 'org-uuid',
            organizationAddress: null,
            status: 'draft',
            source: RegulationOrderRecordSourceEnum::API,
            regulationOrderUuid: 'ro-uuid',
            regulationOrderTemplateUuid: null,
            category: 'temporaryRegulation',
            subject: 'roadMaintenance',
            otherCategoryText: null,
            title: 'Travaux de voirie rue Exemple',
            startDate: new \DateTimeImmutable('2025-10-09T08:00:00+00:00'),
            endDate: new \DateTimeImmutable('2025-10-15T18:00:00+00:00'),
        );

        $measure = new MeasureView(
            uuid: 'measure-uuid',
            type: 'noEntry',
            periods: [],
            vehicleSet: null,
            maxSpeed: null,
            locations: [],
        );

        $this->queryBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->willReturnCallback(function ($query) use ($regulationOrderRecord, $generalInfo, $measure) {
                if ($query instanceof GetRegulationOrderRecordByIdentifierQuery) {
                    return $regulationOrderRecord;
                }

                if ($query instanceof GetGeneralInfoQuery) {
                    return $generalInfo;
                }

                if ($query instanceof GetMeasuresQuery) {
                    return [$measure];
                }

                self::fail('Unexpected query: ' . $query::class);
            });

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(UpdateApiClientLastUsedAtCommand::class));

        $expectedPayload = ['identifier' => 'F2025/001', 'measures' => []];

        $this->normalizer
            ->expects(self::once())
            ->method('normalize')
            ->with(
                self::callback(function ($view): bool {
                    return $view instanceof RegulationApiView
                        && $view->identifier === 'F2025/001'
                        && $view->organization->uuid === 'org-uuid'
                        && $view->organization->name === 'Ma collectivité'
                        && \count($view->measures) === 1
                        && $view->measures[0]->uuid === 'measure-uuid';
                }),
                'json',
                [DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM],
            )
            ->willReturn($expectedPayload);

        $response = ($this->controller)('F2025/001');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedPayload, json_decode($response->getContent(), true));
    }

    public function testGetReturns404WhenRegulationNotFound(): void
    {
        $organization = $this->createMock(Organization::class);

        $user = $this->createMock(ApiClientUser::class);
        $user->method('getOrganization')->willReturn($organization);

        $this->security->method('getUser')->willReturn($user);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(GetRegulationOrderRecordByIdentifierQuery::class))
            ->willThrowException(new RegulationOrderRecordNotFoundException());

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $this->normalizer
            ->expects(self::never())
            ->method('normalize');

        $response = ($this->controller)('DOES-NOT-EXIST');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }
}
