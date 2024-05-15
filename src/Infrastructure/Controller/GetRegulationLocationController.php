<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetRegulationLocationController extends AbstractController
{
    public function __construct(
        private \Twig\Environment $twig,
        private LocationRepositoryInterface $locationRepository,
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    #[Route(
        '/_regulation_location/{uuid}',
        name: 'get_regulation_location',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid = ''): Response
    {
        $location = $this->locationRepository->findOneByUuid($uuid); // uuid of a location
        $measure = $this->measureRepository->findOneByUuid($location->getMeasure()->getUuid());

        return new Response(
            $this->twig->render(
                name: '_regulation_location.html.twig',
                context: [
                    'location' => $location,
                    'measure' => MeasureView::fromEntity($measure),
                ],
            ),
        );
    }
}
