<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class RegulationDeleteController
{
    public function __construct(
        private RouterInterface $router,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_delete',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['DELETE'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var \App\Infrastructure\Security\SymfonyUser */
        $user = $this->security->getUser();

        try {
            $command = new DeleteRegulationCommand($uuid, $user->getOrganizationUuids());
            $status = $this->commandBus->handle($command);
        } catch (RegulationOrderRecordNotFoundException) {
            $status = 'draft';
        } catch (RegulationOrderRecordCannotBeDeletedException) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_regulations_list', ['tab' => $status]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
