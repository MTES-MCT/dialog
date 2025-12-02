<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\SigningAuthority\Fragments;

use App\Application\CommandBusInterface;
use App\Application\Organization\SigningAuthority\Command\SaveSigningAuthorityCommand;
use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\SigningAuthorityFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;

final class SaveSigningAuthorityFragmentController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/_fragment/organizations/{uuid}/signing_authority/save',
        name: 'fragment_organizations_signing_authority_save',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $signingAuthority = $this->queryBus->handle(new GetSigningAuthorityByOrganizationQuery($uuid));
        $command = new SaveSigningAuthorityCommand($organization, $signingAuthority);
        $form = $this->formFactory->create(SigningAuthorityFormType::class, $command, [
            'action' => $this->router->generate('fragment_organizations_signing_authority_save', ['uuid' => $uuid]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('fragment_organizations_signing_authority_preview', [
                    'uuid' => $uuid,
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/signing_authority/fragments/_form.html.twig',
                context: [
                    'form' => $form->createView(),
                    'cancelUrl' => $this->router->generate('fragment_organizations_signing_authority_preview', ['uuid' => $uuid]),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
