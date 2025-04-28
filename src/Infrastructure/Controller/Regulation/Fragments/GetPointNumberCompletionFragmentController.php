<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

final class GetPointNumberCompletionFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    #[Route(
        '/_fragment/point-number-completions',
        name: 'fragment_point_number_completion',
        methods: ['GET'],
    )]
    public function __invoke(
        #[MapQueryParameter] string $search,
        #[MapQueryParameter] string $administrator,
        #[MapQueryParameter] string $roadNumber,
    ): Response {
        $referencePoints = $this->roadGeocoder->findReferencePoints($search, $administrator, $roadNumber);

        $results = [];

        foreach ($referencePoints as $referencePoint) {
            $value = SaveNumberedRoadCommand::encodePointNumberWithDepartmentCode($referencePoint['departmentCode'], $referencePoint['pointNumber']);

            $label = SaveNumberedRoadCommand::makePointNumberWithDepartmentCodeLabel(
                $referencePoint['numDepartments'] > 1 ? $referencePoint['departmentCode'] : null,
                $referencePoint['pointNumber'],
            );

            $results[] = ['value' => $value, 'label' => $label];
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_point_number_completions.html.twig',
                context: [
                    'results' => $results,
                ],
            ),
        );
    }
}
