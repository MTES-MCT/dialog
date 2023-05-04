<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Exception\OrganizationAlreadyHasRegulationOrderWithThisIdentifierException;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
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

        $command = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => [$user->getOrganization()],
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
        $hasCommandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('fragment_regulations_general_info', [
                        'uuid' => $uuid,
                    ]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (OrganizationAlreadyHasRegulationOrderWithThisIdentifierException) {
                $hasCommandFailed = true;
                $form->get('identifier')->addError(
                    new FormError(
                        $this->translator->trans('regulation.general_info.error.identifier', [], 'validators'),
                    ),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_general_info_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'cancelUrl' => $this->router->generate('fragment_regulations_general_info', ['uuid' => $uuid]),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $hasCommandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
