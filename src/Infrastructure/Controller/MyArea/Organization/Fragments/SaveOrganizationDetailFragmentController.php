<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\StorageInterface;
use App\Application\User\Command\SaveOrganizationCommand;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\OrganizationFormType;
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

final class SaveOrganizationDetailFragmentController extends AbstractOrganizationController
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
        '/_fragment/organizations/{uuid}/save',
        name: 'fragment_organizations_save',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $logo = $organization->getLogo() ? $this->storage->getUrl($organization->getLogo()) : null;

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveOrganizationCommand($organization);
        $form = $this->formFactory->create(OrganizationFormType::class, $command, [
            'action' => $this->router->generate('fragment_organizations_save', ['uuid' => $uuid]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('fragment_organizations_preview', [
                    'uuid' => $uuid,
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/fragments/_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'logo' => $logo,
                    'cancelUrl' => $this->router->generate('fragment_organizations_preview', ['uuid' => $uuid]),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
