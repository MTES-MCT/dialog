<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\CommandBusInterface;
use App\Application\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class CommandBus implements CommandBusInterface
{
    use HandleTrait {
        handle as doHandle;
    }

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    public function handle(CommandInterface $command): mixed
    {
        try {
            return $this->doHandle($command);
        } catch (HandlerFailedException $exception) {
            $previous = $exception->getPrevious();

            if ($previous === null) {
                throw $exception;
            }

            throw $previous;
        }
    }
}
