<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\SaveOrganizationUserCommand;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\User\UserFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddUserController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{organizationUuid}/users/add',
        name: 'app_my_area_users_add',
        requirements: ['organizationUuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $organizationUuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveOrganizationUserCommand($organization);
        $form = $this->formFactory->create(UserFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('app_my_area_users_list', ['uuid' => $organizationUuid]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (UserAlreadyRegisteredException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('user.already.registered', [], 'validators')),
                );
            }
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/user/form.html.twig',
                context: [
                    'organization' => $organization,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
