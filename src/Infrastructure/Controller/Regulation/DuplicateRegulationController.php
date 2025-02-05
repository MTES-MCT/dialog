<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationController extends AbstractRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        Security $security,
        QueryBusInterface $queryBus,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/duplicate',
        name: 'app_regulation_duplicate',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('duplicate-regulation')]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        try {
            $duplicatedRegulationOrderRecord = $this->commandBus->handle(
                new DuplicateRegulationCommand($regulationOrderRecord),
            );

            $session->getFlashBag()->add('success', $this->translator->trans('regulation.duplicated.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_regulation_detail', [
                    'uuid' => $duplicatedRegulationOrderRecord->getUuid(),
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (ValidationFailedException $e) {
            $session->getFlashBag()->add('error', $e->getViolations()[0]->getMessage());
        }

        return new RedirectResponse(
            url: $this->router->generate('app_regulations_list'),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
