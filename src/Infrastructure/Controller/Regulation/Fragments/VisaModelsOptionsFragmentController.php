<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\VisaModel\Query\GetVisaModelsQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

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
    public function __invoke(#[MapQueryParameter] string $organizationUuid): Response
    {
        $visaModels = $this->queryBus->handle(new GetVisaModelsQuery($organizationUuid));
        $options = [];

        foreach ($visaModels as $visaModel) {
            $organizationName = $visaModel->organizationUuid ? $visaModel->organizationName : 'DiaLog';
            $options[$organizationName][$visaModel->uuid] = $visaModel->name;
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/visa_models_options.html.twig',
                context: [
                    'options' => $options,
                ],
            ),
        );
    }
}
