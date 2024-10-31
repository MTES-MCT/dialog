<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Organization\VisaModel\Query\GetVisaModelsQuery;
use App\Application\QueryBusInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class VisaModelsOptionsFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/visa_models/options',
        name: 'fragment_visa_models_options',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $organizationUuid,
        #[MapQueryParameter] string $targetId,
    ): Response {
        $visaModels = $this->queryBus->handle(new GetVisaModelsQuery($organizationUuid));
        $options = [];

        foreach ($visaModels as $visaModel) {
            $organizationName = $visaModel->organizationUuid ? $visaModel->organizationName : 'DiaLog';
            $options[$organizationName][$visaModel->uuid] = $visaModel->name;
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/visa_models_options.stream.html.twig',
                context: [
                    'options' => $options,
                    'targetId' => $targetId,
                ],
            ),
        );
    }
}
