<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetLocationController extends AbstractController
{
    public function __construct(
        private \Twig\Environment $twig,
        private LocationRepositoryInterface $locationRepository,
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    #[Route(
        '/_location/{uuid}',
        name: 'get_location',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid = ''): Response
    {
        $location = null;
        $measureAsAView = null;
        $regulation = null;
        $regulationOrderRecordId = null;
        if ($uuid) { // uuid of a location
            $location = $this->locationRepository->findOneByUuid($uuid);
            if ($location) {
                $measure = $this->measureRepository->findOneByUuid($location->getMeasure()->getUuid());
                if ($measure) {
                    $regulation = $measure->getRegulationOrder();
                    $regulationOrderRecordId = $regulation->getRegulationOrderRecord()->getUuid();
                    $measureAsAView = MeasureView::fromEntity($measure);
                }
            }
        }
        if (!$location or !$measureAsAView or !$regulation) {
            throw new NotFoundHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'map/fragments/location.html.twig',
                context: [
                    'location' => $location,
                    'measure' => $measureAsAView,
                    'regulation' => $regulation,
                    'regulationOrderRecordId' => $regulationOrderRecordId,
                ],
            ),
        );
    }
}
