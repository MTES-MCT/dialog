<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetStorageAreasByRoadNumbersQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class StorageAreaOptionsFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/storage_area/options',
        name: 'fragment_storage_area_options',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $roadNumber,
        #[MapQueryParameter] string $targetId,
    ): Response {
        $storageAreas = $this->queryBus->handle(new GetStorageAreasByRoadNumbersQuery([$roadNumber]));

        $options = [];

        if (!empty($storageAreas[$roadNumber])) {
            foreach ($storageAreas[$roadNumber] as $storageArea) {
                $options[$storageArea->getUuid()] = $storageArea->getDescription();
            }
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_storage_area_options.stream.html.twig',
                context: [
                    'options' => $options,
                    'targetId' => $targetId,
                ],
            ),
        );
    }
}
