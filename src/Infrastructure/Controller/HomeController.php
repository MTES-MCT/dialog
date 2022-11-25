<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\RegulationOrder\Query\GetAllRegulationOrdersQuery;
use App\Infrastructure\Form\RegulationOrder\RegulationOrderType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(RegulationOrderType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = $form->getData();
            $this->commandBus->handle($command);

            return new RedirectResponse('/');
        }

        $regulationOrders = $this->queryBus->handle(new GetAllRegulationOrdersQuery());

        $context = [
            'objects' => $regulationOrders,
            'form' => $form->createView(),
        ];

        $html = $this->twig->render('index.html.twig', $context);

        return new Response($html);
    }
}
