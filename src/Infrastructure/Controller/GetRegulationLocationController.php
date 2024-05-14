<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

// use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetRegulationLocationController extends AbstractController
{
    public function __construct(
        private \Twig\Environment $twig,
        // private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    #[Route(
        '/_regulation_location/{uuid}',
        name: 'get_regulation_location',
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid = ''): Response
    {
        // $regulation_location = $this->regulationOrderRecordRepository->;

        return new Response(
            $this->twig->render(
                name: '_regulation_location.html.twig',
                context: [
                    'regulation_location_uuid' => $uuid,
                ],
            ),
        );
    }
}
