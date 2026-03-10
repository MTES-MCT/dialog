<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\AsyncCommandInterface;
use App\Application\CommandBusInterface;
use App\Application\CommandInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

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
            $message = $command instanceof AsyncCommandInterface
                ? Envelope::wrap($command, [new TransportNamesStamp('sync')])
                : $command;

            return $this->doHandle($message);
        } catch (HandlerFailedException $exception) {
            $previous = $exception->getPrevious();

            if ($previous === null) {
                throw $exception;
            }

            throw $previous;
        }
    }

    /**
     * Use whenever you want to execute a command async,
     * but **please ensure the Messenger routing is correct**.
     */
    public function dispatchAsync(AsyncCommandInterface $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
