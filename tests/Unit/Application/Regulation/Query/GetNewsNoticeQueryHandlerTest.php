<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetNewsNoticeQuery;
use App\Application\Regulation\Query\GetNewsNoticeQueryHandler;
use App\Domain\User\News;
use App\Domain\User\Repository\NewsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetNewsNoticeQueryHandlerTest extends TestCase
{
    public function testItReturnsNewsWhenFound(): void
    {
        $news = $this->createMock(News::class);
        $newsRepository = $this->createMock(NewsRepositoryInterface::class);
        $newsRepository
            ->expects(self::once())
            ->method('findLatest')
            ->willReturn($news);

        $handler = new GetNewsNoticeQueryHandler($newsRepository);
        $result = $handler(new GetNewsNoticeQuery());

        $this->assertInstanceOf(News::class, $result);
        $this->assertSame($news, $result);
    }

    public function testItReturnsNullWhenNoNewsFound(): void
    {
        $newsRepository = $this->createMock(NewsRepositoryInterface::class);
        $newsRepository
            ->expects(self::once())
            ->method('findLatest')
            ->willReturn(null);

        $handler = new GetNewsNoticeQueryHandler($newsRepository);
        $result = $handler(new GetNewsNoticeQuery());

        $this->assertNull($result);
    }
}
