<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep5Command;
use App\Application\Regulation\Query\GetRegulationOrderSummaryQuery;
use App\Application\Regulation\View\RegulationOrderSummaryView;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Infrastructure\Form\Regulation\Step5FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Step5Controller
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        '/regulations/form/{uuid}/5',
        name: 'app_regulations_steps_5',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        try {
            /** @var RegulationOrderSummaryView */
            $regulationOrderSummary = $this->queryBus->handle(new GetRegulationOrderSummaryQuery($uuid));
        } catch (RegulationOrderNotFoundException) {
            throw new NotFoundHttpException();
        }

        $command = new SaveRegulationStep5Command($regulationOrderSummary->uuid, $regulationOrderSummary->status);
        $form = $this->formFactory->create(Step5FormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED) {
                    return new RedirectResponse(
                        url: $this->router->generate('app_regulation_detail', [
                            'uuid' => $regulationOrderSummary->uuid,
                        ]),
                        status: Response::HTTP_SEE_OTHER,
                    );
                }

                return new RedirectResponse(
                    url: $this->router->generate('app_regulations_list'),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (RegulationOrderCannotBePublishedException) {
                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans('regulation.step5.regulation_cant_be_published', [], 'validators'),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step5.html.twig',
                context: [
                    'form' => $form->createView(),
                    'stepNumber' => 5,
                    'uuid' => $uuid,
                    'regulation' => $regulationOrderSummary,
                ],
            ),
        );
    }
}
