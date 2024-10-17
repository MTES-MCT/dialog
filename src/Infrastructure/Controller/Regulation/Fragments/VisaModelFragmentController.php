<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\VisaModel\Query\GetVisaModelQuery;
use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class VisaModelFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/visa_models/detail',
        name: 'fragment_visa_model',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $visaModelUuid,
    ): Response {
        try {
            $visaModel = $this->queryBus->handle(new GetVisaModelQuery($visaModelUuid));
        } catch (VisaModelNotFoundException) {
            throw new NotFoundHttpException();
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/visa_model.stream.html.twig',
                context: [
                    'visaModel' => $visaModel,
                ],
            ),
        );
    }
}
