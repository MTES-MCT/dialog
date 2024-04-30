<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
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
    public function __invoke(): Response
    {
        $regulationOrdersAsGeoJson = $this->regulationOrderRecordRepository->findRegulationOrdersAsGeoJson();
        $regulationOrdersBbox = $this->regulationOrderRecordRepository->findRegulationOrdersBbox();

        return new Response(
            $this->twig->render(
                name: 'map.html.twig',
                context: [
                    'regulationOrdersAsGeoJson' => $regulationOrdersAsGeoJson,
                    'regulationOrdersBbox' => $regulationOrdersBbox,
                ],
            ),
        );
    }
}
