<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Infrastructure\Form\Regulation\Step4FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class Step4Controller extends AbstractStepsController
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
        '/regulations/form/{uuid}/4',
        name: 'app_regulations_steps_4',
        requirements: ['uuid' => '.+'],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $regulationCondition = $regulationOrderRecord->getRegulationOrder()->getRegulationCondition();
        $vehicleCharacteristics = $this->queryBus->handle(
            new GetVehicleCharacteristicsByRegulationConditionQuery($regulationCondition->getUuid()),
        );

        $command = SaveRegulationStep4Command::create($regulationOrderRecord, $vehicleCharacteristics);
        $form = $this->formFactory->create(Step4FormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_5', ['uuid' => $uuid]),
                status: Response::HTTP_FOUND,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step4.html.twig',
                context: [
                    'form' => $form->createView(),
                    'stepNumber' => 4,
                    'uuid' => $uuid,
                ],
            ),
        );
    }
}
