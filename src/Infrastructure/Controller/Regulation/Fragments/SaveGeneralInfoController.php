<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Turbo\TurboBundle;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private CanDeleteMeasures $canDeleteMeasures,
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
        /** @var AbstractAuthenticatedUser */
        $user = $this->security->getUser();
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $organizationUuid = $regulationOrderRecord->getOrganizationUuid();

        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organizationUuid;
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));

        $command = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);
        $initialCategory = $command->category;

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => $user->getUserOrganizations(),
                'regulationOrderTemplates' => $regulationOrderTemplates,
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

            $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
            // La catégorie (permanent/temporaire) conditionne le titre de la page
            // et les champs de période des formulaires de mesure : ces blocs sont
            // re-rendus par le stream quand elle change.
            $categoryChanged = $command->category !== $initialCategory;
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return new Response(
                $this->twig->render(
                    name: 'regulation/fragments/_general_info.updated.stream.html.twig',
                    context: [
                        'generalInfo' => $generalInfo,
                        'canEdit' => $generalInfo->isSourceDialog(),
                        'regulationOrderRecord' => $regulationOrderRecord,
                        'categoryChanged' => $categoryChanged,
                        'measures' => $categoryChanged ? $this->queryBus->handle(new GetMeasuresQuery($uuid)) : [],
                        'canDelete' => $this->canDeleteMeasures->isSatisfiedBy($regulationOrderRecord),
                    ],
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
