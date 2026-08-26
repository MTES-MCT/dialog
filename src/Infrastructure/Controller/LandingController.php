<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetLatestRegulationsQuery;
use App\Application\Regulation\Query\GetRegulationCountsByStatusQuery;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
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
        private DateUtilsInterface $dateUtils,
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

        // La carte du tableau de bord n'a pas d'UI de filtres : on affiche toutes les restrictions
        // en vigueur ou à venir, y compris celles spécifiques aux poids lourds et tous les types de
        // mesure (la page Carte, elle, se limite par défaut aux interdictions de circulation hors
        // poids lourds, cf. MapFilterDTO).
        $tilesQuery = http_build_query([
            'map_filter_form' => [
                'measureTypes' => array_map(static fn (MeasureTypeEnum $case) => $case->value, MeasureTypeEnum::cases()),
                'displayPermanentRegulations' => 'yes',
                'displayTemporaryRegulations' => 'yes',
                'displayHeavyGoodsVehicles' => 'yes',
                'startDate' => $this->dateUtils->getNow()->format('Y-m-d'),
            ],
        ]);

        return new Response($this->twig->render(
            name: 'dashboard/index.html.twig',
            context: [
                'regulationCounts' => $this->queryBus->handle(new GetRegulationCountsByStatusQuery($organizationUuids)),
                'latestRegulations' => $this->queryBus->handle(new GetLatestRegulationsQuery($organizationUuids)),
                'initialBbox' => $this->organizationRepository->findMapBboxByOrganizationUuids($organizationUuids),
                // Voir le commentaire dans MapController à propos des placeholders {z}/{x}/{y}.
                'tilesUrlTemplate' => '/carte/tiles/{z}/{x}/{y}.mvt?' . $tilesQuery,
            ],
        ));
    }
}
