<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;

final readonly class UpdateNamedStreetsWithoutRoadBanIdsCommand implements CommandInterface
{
    private mixed $onEventCallback;

    public function __construct(
        callable $onEventCallback,
    ) {
        $this->onEventCallback = $onEventCallback;
    }

    public function onEvent(array $event)
    {
        \call_user_func($this->onEventCallback, $event);
    }
}
