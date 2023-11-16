<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Feedback;

interface FeedbackRepositoryInterface
{
    public function add(Feedback $feedback): Feedback;
}
