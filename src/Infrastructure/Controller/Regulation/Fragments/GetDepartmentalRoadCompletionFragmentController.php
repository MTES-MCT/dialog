<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadsNumbersInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetDepartmentalRoadCompletionFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadsNumbersInterface $roadsNumbers,
    ) {
    }

    #[Route(
        '/_fragment/roads_numbers-completions',
        methods: 'GET',
        name: 'fragment_roads_numbers_completion',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');
        $administrator = $request->query->get('administrator');

        if (!$administrator) {
            throw new BadRequestHttpException();
        }

        $roadType = 'Départementale';
        $departmentalRoadsNumbers = $this->roadsNumbers->getDepartmentalRoad($search, $administrator, $roadType);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_roads_numbers_completions.html.twig',
                context: [
                    'departmentalRoadsNumbers' => $departmentalRoadsNumbers,
                ],
            ),
        );
    }
}
