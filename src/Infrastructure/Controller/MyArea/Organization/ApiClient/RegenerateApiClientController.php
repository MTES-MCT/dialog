<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Application\CommandBusInterface;
use App\Application\Organization\ApiClient\Command\RegenerateApiClientSecretCommand;
use App\Application\Organization\ApiClient\View\ApiClientCreatedView;
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

final class RegenerateApiClientController extends AbstractOrganizationController
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
        path: '/organizations/{uuid}/api-clients/{apiClientUuid}/regenerate',
        name: 'app_config_api_clients_regenerate',
        requirements: ['uuid' => Requirement::UUID, 'apiClientUuid' => Requirement::UUID],
        methods: ['POST'],
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
            /** @var ApiClientCreatedView $result */
            $result = $this->commandBus->handle(new RegenerateApiClientSecretCommand($apiClientUuid));
        } catch (ApiClientNotFoundException) {
            throw new NotFoundHttpException();
        }

        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();
        $session->getFlashBag()->add(
            'success',
            $this->translator->trans('api_client.regenerate.flash_success', [
                '%client_id%' => htmlspecialchars($result->clientId, \ENT_QUOTES, 'UTF-8'),
                '%client_secret%' => htmlspecialchars($result->clientSecret, \ENT_QUOTES, 'UTF-8'),
            ]),
        );

        return new RedirectResponse(
            url: $this->router->generate('app_config_api_clients_list', ['uuid' => $uuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
