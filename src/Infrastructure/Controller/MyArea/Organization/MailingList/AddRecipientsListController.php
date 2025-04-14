<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Application\CommandBusInterface;
use App\Application\Organization\MailingList\Command\SaveMailingListCommand;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\MailingListFormType;
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

final class AddRecipientsListController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        path: '/organizations/{uuid}/recipients/add',
        name: 'app_config_recipients_list_add',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }
        $command = new SaveMailingListCommand($organization);
        $form = $this->formFactory->create(MailingListFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_recipients_list', ['uuid' => $uuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response($this->twig->render(
            name: 'my_area/organization/mailing_list/form.html.twig',
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
