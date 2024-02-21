<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class AddMeasureLinkFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/add-link',
        methods: 'GET',
        name: 'fragment_regulation_measure_add_link',
        requirements: [
            'regulationOrderRecordUuid' => Requirement::UUID,
        ],
    )]
    public function __invoke(string $regulationOrderRecordUuid): Response
    {
        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_add_measure_link.html.twig',
                context: [
                    'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                ],
            ),
        );
    }
}
