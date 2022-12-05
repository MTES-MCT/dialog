<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\IdFactoryInterface;
use Symfony\Component\Uid\Uuid;

final class IdFactory implements IdFactoryInterface
{
    public function make(): string
    {
        return (string) Uuid::v4();
    }
}
