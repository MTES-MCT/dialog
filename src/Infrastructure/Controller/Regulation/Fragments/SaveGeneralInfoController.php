<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Domain\Regulation\Specification\CanEditRegulationOrderRecord;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        QueryBusInterface $queryBus,
        CanEditRegulationOrderRecord $canEditRegulationOrderRecord,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation, $canEditRegulationOrderRecord);
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
        $this->assertRegulationOrderRecordContentEditable($regulationOrderRecord);
        $organizationUuid = $regulationOrderRecord->getOrganizationUuid();

        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organizationUuid;
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));

        $command = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);

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
            $wasPublished = !$regulationOrderRecord->isDraft();
            $this->commandBus->handle($command);

            if ($wasPublished) {
                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans('regulation.edit_published.saved'),
                );
            }

            return new RedirectResponse(
                url: $this->router->generate('fragment_regulations_general_info', [
                    'uuid' => $uuid,
                ]),
                status: Response::HTTP_SEE_OTHER,
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
