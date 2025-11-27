<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Security\Voter\RegulationOrderRecordVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class PublishRegulationController extends AbstractRegulationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/publish',
        name: 'app_regulation_publish',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('publish-regulation')]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        if (!$this->security->isGranted(RegulationOrderRecordVoter::PUBLISH, $regulationOrderRecord)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        } catch (RegulationOrderRecordCannotBePublishedException) {
            throw new AccessDeniedHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_regulation_detail', [
                'uuid' => $uuid,
            ]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
