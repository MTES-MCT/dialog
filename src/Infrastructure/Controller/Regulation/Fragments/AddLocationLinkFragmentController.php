<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AddLocationLinkFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/location/add-link',
        methods: 'GET',
        name: 'fragment_regulation_location_add_link',
        requirements: [
            'regulationOrderRecordUuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        ],
    )]
    public function __invoke(string $regulationOrderRecordUuid): Response
    {
        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_add_location_link.html.twig',
                context: [
                    'regulationOrderRecordUuid' => $regulationOrderRecordUuid,
                ],
            ),
        );
    }
}
