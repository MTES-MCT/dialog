<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep2Command;
use App\Infrastructure\Form\Regulation\Step2FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class Step2Controller extends AbstractStepsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/regulations/form/{uuid}/2',
        name: 'app_regulations_steps_2',
        requirements: ['uuid' => '.+'],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $regulationCondition = $regulationOrderRecord->getRegulationOrder()->getRegulationCondition();
        $location = $this->queryBus->handle(
            new GetLocationByRegulationConditionQuery($regulationCondition->getUuid()),
        );

        $command = SaveRegulationStep2Command::create($regulationOrderRecord, $location);
        $form = $this->formFactory->create(Step2FormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_3', ['uuid' => $uuid]),
                status: Response::HTTP_FOUND,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step2.html.twig',
                context: [
                    'form' => $form->createView(),
                    'stepNumber' => 2,
                    'uuid' => $uuid,
                ],
            ),
        );
    }
}
