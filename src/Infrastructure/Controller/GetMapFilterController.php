<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetMapFilterController extends AbstractController
{
    public function __construct(
        private \Twig\Environment $twig,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/_get_map_filter',
        name: 'get_map_filter',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $permanentAndOrTemporaryFilter = $request->query->get('permanent-and-or-temporary', 'both-permanent-and-temporary');
        $draftFilter = $request->query->get('display-drafts', 'no');

        $locationsAsGeoJson = $this->locationRepository->findFilteredLocationsAsGeoJson($permanentAndOrTemporaryFilter, $draftFilter);

        return new Response(
            $this->twig->render(
                name: '_map_filter.html.twig',
                context: [
                    'permanentAndOrTemporaryFilter' => $permanentAndOrTemporaryFilter,
                    'draftFilter' => $draftFilter,

                    'locationsAsGeoJson' => $locationsAsGeoJson,
                ],
            ),
        );
    }
}
