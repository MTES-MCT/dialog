<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\RegulationOrderTemplate\SaveRegulationOrderTemplateCommand;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\RegulationOrderTemplateFormType;
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

final class AddRegulationOrderTemplateController extends AbstractOrganizationController
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
        '/organizations/{organizationUuid}/regulation_order_templates/add',
        name: 'app_config_regulation_order_templates_add',
        requirements: ['organizationUuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $organizationUuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveRegulationOrderTemplateCommand($organization);
        $form = $this->formFactory->create(RegulationOrderTemplateFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_regulation_order_templates_list', ['uuid' => $organizationUuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/regulation_order_template/form.html.twig',
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
