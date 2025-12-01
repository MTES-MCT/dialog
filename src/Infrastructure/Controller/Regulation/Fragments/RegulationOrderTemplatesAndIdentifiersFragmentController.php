<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQuery;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class RegulationOrderTemplatesAndIdentifiersFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/_fragment/regulation_order_templates_and_identifiers',
        name: 'fragment_regulation_order_templates_and_identifiers',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $organizationUuid,
        #[MapQueryParameter] string $identifierTargetId,
        #[MapQueryParameter] string $identifierTargetName,
        #[MapQueryParameter] string $targetId,
    ): Response {
        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organizationUuid;
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));
        $options = [];

        foreach ($regulationOrderTemplates as $regulationOrderTemplate) {
            $options[$regulationOrderTemplate->uuid] = $regulationOrderTemplate->name;
        }

        $identifier = $this->queryBus->handle(new GetRegulationOrderIdentifierQuery($organizationUuid));

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/regulation_order_templates_and_identifiers.stream.html.twig',
                context: [
                    'options' => $options,
                    'targetId' => $targetId,
                    'organizationUuid' => $organizationUuid,
                    'identifierTargetId' => $identifierTargetId,
                    'identifierTargetName' => $identifierTargetName,
                    'identifierValue' => $identifier,
                ],
            ),
        );
    }
}
