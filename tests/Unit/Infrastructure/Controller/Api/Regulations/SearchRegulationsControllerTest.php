<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForApiQuery;
use App\Application\Regulation\View\RegulationOrderForApiView;
use App\Domain\Pagination;
use App\Domain\User\Organization;
use App\Infrastructure\Controller\Api\Regulations\SearchRegulationsController;
use App\Infrastructure\Security\User\ApiClientUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SearchRegulationsControllerTest extends TestCase
{
    private QueryBusInterface&MockObject $queryBus;
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;
    private NormalizerInterface&MockObject $normalizer;
    private SearchRegulationsController $controller;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);

        $this->controller = new SearchRegulationsController(
            $this->queryBus,
            $this->commandBus,
            $this->security,
            $this->normalizer,
        );
    }

    private function authenticate(): void
    {
        $user = $this->createMock(ApiClientUser::class);
        $user->method('getOrganization')->willReturn($this->createMock(Organization::class));
        $user->method('getUserIdentifier')->willReturn('clientId');
        $this->security->method('getUser')->willReturn($user);
    }

    public function testReturnsPaginatedEnvelope(): void
    {
        $this->authenticate();

        $view = new RegulationOrderForApiView(
            identifier: 'F/1',
            status: 'published',
            category: 'temporaryRegulation',
            subject: null,
            otherCategoryText: null,
            title: 'Title',
            startDate: null,
            endDate: null,
            organizationUuid: 'org-uuid',
            organizationName: 'Org',
            measures: [],
        );

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(GetRegulationOrdersForApiQuery::class))
            ->willReturn(new Pagination([$view], 5, 1, 20));

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(UpdateApiClientLastUsedAtCommand::class));

        $this->normalizer
            ->expects(self::once())
            ->method('normalize')
            ->willReturn([['identifier' => 'F/1']]);

        $response = ($this->controller)('current');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['metadata']['page']);
        $this->assertSame(20, $data['metadata']['pageSize']);
        $this->assertSame(5, $data['metadata']['totalItems']);
        $this->assertSame(1, $data['metadata']['lastPage']);
        $this->assertSame([['identifier' => 'F/1']], $data['regulations']);
    }

    public function testRejectsInvalidStatus(): void
    {
        $this->queryBus->expects(self::never())->method('handle');

        $response = ($this->controller)('invalid');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', json_decode($response->getContent(), true));
    }

    public function testRejectsInvalidCategory(): void
    {
        $this->queryBus->expects(self::never())->method('handle');

        $response = ($this->controller)('current', category: 'nope');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRejectsInvalidMeasureType(): void
    {
        $this->queryBus->expects(self::never())->method('handle');

        $response = ($this->controller)('current', measureType: 'nope');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRejectsInvalidDate(): void
    {
        $this->queryBus->expects(self::never())->method('handle');

        $response = ($this->controller)('current', dateStart: 'not-a-date');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testClampsPageAndPageSize(): void
    {
        $this->authenticate();

        $captured = null;
        $this->queryBus
            ->method('handle')
            ->willReturnCallback(function ($query) use (&$captured) {
                if ($query instanceof GetRegulationOrdersForApiQuery) {
                    $captured = $query;

                    return new Pagination([], 0, 1, 100);
                }

                return null;
            });
        $this->normalizer->method('normalize')->willReturn([]);

        ($this->controller)('all', page: 0, pageSize: 9999);

        $this->assertSame(1, $captured->page);
        $this->assertSame(100, $captured->pageSize);
    }
}
