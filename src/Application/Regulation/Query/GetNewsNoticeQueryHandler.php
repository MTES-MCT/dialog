<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\User\News;
use App\Domain\User\Repository\NewsRepositoryInterface;

final class GetNewsNoticeQueryHandler
{
    public function __construct(
        private NewsRepositoryInterface $newsRepository,
    ) {
    }

    public function __invoke(GetNewsNoticeQuery $query): ?News
    {
        return $this->newsRepository->findLatest();
    }
}
