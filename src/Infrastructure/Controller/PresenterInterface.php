<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

interface PresenterInterface
{
    public function present(array $context): array;
}
