<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\RegulationOrderTemplate\SaveRegulationOrderTemplateCommand;
use App\Application\Regulation\Query\RegulationOrderTemplate\GetRegulationOrderTemplateQuery;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Organization\RegulationOrderTemplateFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;

final class EditRegulationOrderTemplateController extends AbstractOrganizationController
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
        '/organizations/{uuid}/regulation_order_templates/{regulationOrderTemplateUuid}/edit',
        name: 'app_config_regulation_order_template_edit',
        requirements: ['uuid' => Requirement::UUID, 'regulationOrderTemplateUuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid, string $regulationOrderTemplateUuid): Response
    {
        $organization = $this->getOrganization($uuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $regulationOrderTemplate = $this->queryBus->handle(new GetRegulationOrderTemplateQuery($regulationOrderTemplateUuid));
        if (!$regulationOrderTemplate) {
            throw new NotFoundHttpException();
        }

        $command = new SaveRegulationOrderTemplateCommand($organization, $regulationOrderTemplate);
        $form = $this->formFactory->create(RegulationOrderTemplateFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_regulation_order_templates_list', ['uuid' => $uuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/regulation_order_template/form.html.twig',
                context: [
                    'organization' => $organization,
                    'regulationOrderTemplate' => $regulationOrderTemplate,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
