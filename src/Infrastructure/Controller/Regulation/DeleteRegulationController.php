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
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class DeleteRegulationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_delete',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $csrfToken = new CsrfToken('delete-regulation', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }

        /** @var SymfonyUser */
        $user = $this->security->getUser();

        try {
            /** @var RegulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            // The regulation may have been deleted before.
            // Don't fail, as DELETE is an idempotent method (see RFC 9110, 9.2.2).
            return new RedirectResponse(
                url: $this->router->generate('app_regulations_list', [
                    'tab' => 'temporary',
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        // Get before the entity gets deleted.
        $endDate = $regulationOrderRecord->getRegulationOrder()->getEndDate();

        try {
            $this->commandBus->handle(new DeleteRegulationCommand($user->getOrganizationUuids(), $regulationOrderRecord));
        } catch (RegulationOrderRecordCannotBeDeletedException) {
            throw new AccessDeniedHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_regulations_list', [
                'tab' => $endDate ? 'temporary' : 'permanent',
            ]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
