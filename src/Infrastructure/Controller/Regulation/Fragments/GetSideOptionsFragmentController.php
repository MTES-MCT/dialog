<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class GetSideOptionsFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    #[Route(
        '/_fragment/side-options',
        name: 'fragment_side_options',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $administrator,
        #[MapQueryParameter] string $roadNumber,
        #[MapQueryParameter] string $pointNumberWithDepartmentCode,
        #[MapQueryParameter] string $currentOption,
        #[MapQueryParameter] string $targetId,
    ): Response {
        [$departmentCode, $pointNumber] = SaveNumberedRoadCommand::decodePointNumberWithDepartmentCode($pointNumberWithDepartmentCode);

        $sides = $this->roadGeocoder->findSides($administrator, $roadNumber, $departmentCode, $pointNumber);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_side_options.stream.html.twig',
                context: [
                    'sides' => $sides,
                    'currentOption' => $currentOption,
                    'targetId' => $targetId,
                ],
            ),
        );
    }
}
