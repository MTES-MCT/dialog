<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Organization\VisaModel\Query\GetVisaModelsQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Query\GetRegulationOrderHistoryQuery;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Turbo\TurboBundle;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/general_info/form/{uuid}',
        name: 'fragment_regulations_general_info_form',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $visaModels = $this->queryBus->handle(new GetVisaModelsQuery($regulationOrderRecord->getOrganizationUuid()));
        $command = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => $user->getUserOrganizations(),
                'visaModels' => $visaModels,
                'action' => $this->router->generate('fragment_regulations_general_info_form', ['uuid' => $uuid]),
                'save_options' => [
                    'label' => 'common.form.validate',
                    'attr' => [
                        'class' => 'fr-btn',
                    ],
                ],
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            $latestHistory = $this->queryBus->handle(new GetRegulationOrderHistoryQuery($regulationOrderRecord->getRegulationOrder()->getUuid()));

            return new Response(
                $this->twig->render(
                    name: 'regulation/fragments/_general_info.updated.stream.html.twig',
                    context: ['latestHistory' => $latestHistory, 'generalInfoUuid' => $uuid],
                ),
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_general_info_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'cancelUrl' => $this->router->generate('fragment_regulations_general_info', ['uuid' => $uuid]),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
