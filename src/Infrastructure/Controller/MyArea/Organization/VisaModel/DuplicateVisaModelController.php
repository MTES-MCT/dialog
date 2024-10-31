<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Application\CommandBusInterface;
use App\Application\Organization\VisaModel\Command\DuplicateVisaModelCommand;
use App\Application\QueryBusInterface;
use App\Domain\Organization\VisaModel\Exception\VisaModelNotFoundException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateVisaModelController extends AbstractOrganizationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{organizationUuid}/visa_models/{uuid}/duplicate',
        name: 'app_my_area_config_visa_models_duplicate',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('duplicate-visa-model')]
    public function __invoke(Request $request, string $organizationUuid, string $uuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $this->commandBus->handle(new DuplicateVisaModelCommand($organization, $uuid));
            $session->getFlashBag()->add('success', $this->translator->trans('visa.duplicated.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_my_area_config_visa_models_list', ['uuid' => $organizationUuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (VisaModelNotFoundException $e) {
            throw new NotFoundHttpException();
        }
    }
}
