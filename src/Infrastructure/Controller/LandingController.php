<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetLatestRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationCountsByStatusQuery;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private AuthenticatedUser $authenticatedUser,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    #[Route('/', name: 'app_landing', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->authenticatedUser->getSessionUser();

        if (!$user) {
            return new Response($this->twig->render('index.html.twig'));
        }

        $organizationUuids = $user->getUserOrganizationUuids();

        return new Response($this->twig->render(
            name: 'dashboard/index.html.twig',
            context: [
                'regulationCounts' => $this->queryBus->handle(new GetRegulationCountsByStatusQuery($organizationUuids)),
                'latestRegulations' => $this->queryBus->handle(new GetLatestRegulationsQuery($organizationUuids)),
                'initialBbox' => $this->organizationRepository->findMapBboxByOrganizationUuids($organizationUuids),
                // Voir le commentaire dans MapController à propos des placeholders {z}/{x}/{y}.
                'tilesUrlTemplate' => '/carte/tiles/{z}/{x}/{y}.mvt',
            ],
        ));
    }
}
