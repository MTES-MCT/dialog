<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private Security $security,
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/_fragment/regulations/general_info/form/{uuid}',
        name: 'fragment_regulations_general_info_form',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid = null): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();

        $isEdit = $uuid !== null;

        $regulationOrderRecord = $isEdit ? $this->getRegulationOrderRecord($uuid) : null;

        if ($uuid && !$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getOrganization())) {
            throw new AccessDeniedHttpException();
        }

        // TODO: rename to SaveRegulationGeneralInfoCommand
        $command = SaveRegulationOrderCommand::create($regulationOrderRecord);

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => [$user->getOrganization()],
                'isEdit' => $isEdit,
                'action' => $this->router->generate('fragment_regulations_general_info_form', ['uuid' => $uuid]),
            ],
        );

        $form->handleRequest($request);

        $hasCommandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $regulationOrderRecord = $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate($isEdit ? 'fragment_regulations_general_info' : 'app_regulation_detail', [
                        'uuid' => $regulationOrderRecord->getUuid(),
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
                    'isEdit' => $isEdit,
                    'uuid' => $uuid,
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $hasCommandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
