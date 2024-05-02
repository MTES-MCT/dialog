<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    #[Route('/carte', name: 'app_carto', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $permanentAndOrTemporaryFilter = $request->query->get('permanent-and-or-temporary', 'both-permanent-and-temporary');
        $draftFilter = $request->query->get('display-drafts', 'no');

        $regulationOrdersAsGeoJson = $this->regulationOrderRecordRepository->findRegulationOrdersAsGeoJson($permanentAndOrTemporaryFilter, $draftFilter);
        $regulationOrdersBbox = $this->regulationOrderRecordRepository->findRegulationOrdersBbox();

        return new Response(
            $this->twig->render(
                name: 'map.html.twig',
                context: [
                    'regulationOrdersAsGeoJson' => $regulationOrdersAsGeoJson,
                    'regulationOrdersBbox' => $regulationOrdersBbox,

                    'permanentAndOrTemporaryFilter' => $permanentAndOrTemporaryFilter,
                    'draftFilter' => $draftFilter,
                ],
            ),
        );
    }
}
