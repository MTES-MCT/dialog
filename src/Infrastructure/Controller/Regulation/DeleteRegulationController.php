<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteRegulationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_delete',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-regulation')]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var AbstractAuthenticatedUser */
        $user = $this->security->getUser();

        try {
            /** @var RegulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            // The regulation may have been deleted before.
            // Don't fail, as DELETE is an idempotent method (see RFC 9110, 9.2.2).
            return new RedirectResponse(
                url: $this->router->generate('app_regulations_list'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        try {
            $this->commandBus->handle(new DeleteRegulationCommand($user->getUserOrganizationUuids(), $regulationOrderRecord));
        } catch (RegulationOrderRecordCannotBeDeletedException) {
            throw new AccessDeniedHttpException();
        }

        $redirectQueryParams = $request->query->get('_redirectQueryParams')
            ? json_decode($request->query->get('_redirectQueryParams'), true)
            : [];

        return new RedirectResponse(
            url: $this->router->generate('app_regulations_list', $redirectQueryParams),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
