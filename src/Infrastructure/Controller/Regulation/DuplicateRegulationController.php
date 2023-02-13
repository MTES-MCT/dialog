<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Domain\Regulation\Exception\RegulationCannotBeDuplicated;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class DuplicateRegulationController extends AbstractRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/regulations/{uuid}/duplicate',
        name: 'app_regulation_duplicate',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $csrfToken = new CsrfToken('duplicate-regulation', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }

        /** @var SymfonyUser */
        $user = $this->security->getUser();
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        try {
            $duplicatedRegulationOrderRecord = $this->commandBus->handle(
                new DuplicateRegulationCommand($user->getOrganization(), $regulationOrderRecord),
            );

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_5', [
                    'uuid' => $duplicatedRegulationOrderRecord->getUuid(),
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (RegulationCannotBeDuplicated) {
            throw new AccessDeniedHttpException();
        }
    }
}
