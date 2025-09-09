<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class RegulationOrderTemplatesOptionsFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/regulation_order_templates/options',
        name: 'fragment_regulation_order_templates_options',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $organizationUuid,
        #[MapQueryParameter] string $targetId,
    ): Response {
        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organizationUuid;
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));
        $options = [];

        foreach ($regulationOrderTemplates as $regulationOrderTemplate) {
            $options[$regulationOrderTemplate->uuid] = $regulationOrderTemplate->name;
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/regulation_order_templates_options.stream.html.twig',
                context: [
                    'options' => $options,
                    'targetId' => $targetId,
                    'organizationUuid' => $organizationUuid,
                ],
            ),
        );
    }
}
