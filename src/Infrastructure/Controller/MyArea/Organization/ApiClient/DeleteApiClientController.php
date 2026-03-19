<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Application\CommandBusInterface;
use App\Application\Organization\ApiClient\Command\DeleteApiClientCommand;
use App\Application\QueryBusInterface;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteApiClientController extends AbstractOrganizationController
{
    public function __construct(
        QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private ApiClientRepositoryInterface $apiClientRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        path: '/organizations/{uuid}/api-clients/{apiClientUuid}',
        name: 'app_config_api_clients_delete',
        requirements: ['uuid' => Requirement::UUID, 'apiClientUuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    public function __invoke(Request $request, string $uuid, string $apiClientUuid): Response
    {
        $organization = $this->getOrganization($uuid);
        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $apiClient = $this->apiClientRepository->findOneByUuid($apiClientUuid);
        if ($apiClient === null || $apiClient->getOrganization()->getUuid() !== $uuid) {
            throw new NotFoundHttpException();
        }

        $user = $apiClient->getUser();
        if ($user !== null) {
            $organizationUser = $this->organizationUserRepository->findOrganizationUser($uuid, $user->getUuid());
            $isTargetOwner = $organizationUser?->isOwner() ?? false;
            $isCurrentUserOwner = $this->security->isGranted(OrganizationVoter::OWNER, $organization);
            if ($isTargetOwner && !$isCurrentUserOwner) {
                throw new AccessDeniedHttpException();
            }
        }

        try {
            $this->commandBus->handle(new DeleteApiClientCommand($apiClientUuid));
        } catch (ApiClientNotFoundException) {
            throw new NotFoundHttpException();
        }

        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('api_client.delete.flash_success'));

        return new RedirectResponse(
            url: $this->router->generate('app_config_api_clients_list', ['uuid' => $uuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
