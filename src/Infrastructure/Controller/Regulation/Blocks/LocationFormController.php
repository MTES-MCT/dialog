<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Blocks;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\LocationFormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LocationFormController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/_fragment/regulations/{uuid}/location/form',
        name: 'fragment_regulations_location_form',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        $location = $this->queryBus->handle(
            new GetLocationByRegulationOrderQuery($regulationOrderRecord->getRegulationOrder()->getUuid()),
        );

        $command = SaveRegulationLocationCommand::create($regulationOrderRecord, $location);

        $form = $this->formFactory->create(
            type: LocationFormType::class,
            data: $command,
            options: ['action' => $request->getUri()],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandFailed = false;

            try {
                $this->commandBus->handle($command);
            } catch (GeocodingFailureException $exc) {
                $commandFailed = true;
                \Sentry\captureException($exc);
                $form->addError(
                    new FormError(
                        $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators'),
                    ),
                );
            }

            if (!$commandFailed) {
                return new RedirectResponse(
                    url: $this->router->generate('fragment_regulations_location', ['uuid' => $uuid]),
                    status: Response::HTTP_SEE_OTHER,
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_location_form.html.twig',
                context: ['form' => $form->createView(), 'uuid' => $uuid],
            ),
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
