<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization;

use App\Application\CommandBusInterface;
use App\Application\Organization\Logo\Command\SaveOrganizationLogoCommand;
use App\Application\QueryBusInterface;
use App\Application\StorageInterface;
use App\Infrastructure\Form\Organization\LogoFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;

final class EditOrganizationLogoController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private StorageInterface $storage,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/logo/edit',
        name: 'app_config_organization_edit_logo',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveOrganizationLogoCommand($organization);
        $form = $this->formFactory->create(LogoFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_organization_edit_logo', ['uuid' => $uuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        $logo = $organization->getLogo() ? $this->storage->getUrl($organization->getLogo()) : null;

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/logo.html.twig',
                context: [
                    'organization' => $organization,
                    'form' => $form->createView(),
                    'logo' => $logo,
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
