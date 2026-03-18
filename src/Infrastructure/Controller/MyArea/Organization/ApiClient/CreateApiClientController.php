<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Application\CommandBusInterface;
use App\Application\Organization\ApiClient\Command\CreateApiClientForUserCommand;
use App\Application\Organization\ApiClient\View\ApiClientCreatedView;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationUsersQuery;
use App\Domain\Organization\Exception\UserAlreadyHasApiClientForOrganizationException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\CreateApiClientFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateApiClientController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        path: '/organizations/{uuid}/api-clients/create',
        name: 'app_config_api_clients_create',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $users = $this->queryBus->handle(new GetOrganizationUsersQuery($uuid));
        $form = $this->formFactory->create(CreateApiClientFormType::class, null, [
            'users' => $users,
            'organization_uuid' => $uuid,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userUuid = $form->get('user')->getData();
            try {
                /** @var ApiClientCreatedView $result */
                $result = $this->commandBus->handle(new CreateApiClientForUserCommand($uuid, $userUuid));

                /** @var FlashBagAwareSessionInterface $session */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans('api_client.create.flash_success', [
                        '%client_id%' => htmlspecialchars($result->clientId, \ENT_QUOTES, 'UTF-8'),
                        '%client_secret%' => htmlspecialchars($result->clientSecret, \ENT_QUOTES, 'UTF-8'),
                    ]),
                );

                return new RedirectResponse(
                    url: $this->router->generate('app_config_api_clients_list', ['uuid' => $uuid]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (UserAlreadyHasApiClientForOrganizationException) {
                $form->addError(new FormError(
                    $this->translator->trans('api_client.create.error_already_has_key'),
                ));
            }
        }

        return new Response($this->twig->render(
            name: 'my_area/organization/api_client/create.html.twig',
            context: [
                'organization' => $organization,
                'form' => $form->createView(),
            ],
        ), status: ($form->isSubmitted() && !$form->isValid())
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK);
    }
}
