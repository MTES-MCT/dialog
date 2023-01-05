<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Infrastructure\Form\Regulation\Step3FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class Step3Controller extends AbstractStepsController
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
        '/regulations/form/{uuid}/3',
        name: 'app_regulations_steps_3',
        requirements: ['uuid' => '.+'],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $regulationCondition = $regulationOrderRecord->getRegulationOrder()->getRegulationCondition();
        $overallPeriod = $this->queryBus->handle(
            new GetOverallPeriodByRegulationConditionQuery($regulationCondition->getUuid()),
        );

        $command = new SaveRegulationStep3Command($regulationOrderRecord, $overallPeriod);
        $form = $this->formFactory->create(Step3FormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_4', ['uuid' => $uuid]),
                status: Response::HTTP_FOUND,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step3.html.twig',
                context: [
                    'form' => $form->createView(),
                    'stepNumber' => 3,
                ],
            ),
        );
    }
}
