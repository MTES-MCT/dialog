<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RegulationDeleteController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_delete',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['DELETE'],
    )]
    public function __invoke(string $uuid): Response
    {
        $command = new DeleteRegulationCommand($uuid);

        $this->commandBus->handle($command);

        return new Response(
            status: 204,
        );
    }
}
