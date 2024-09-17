<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization\VisaModel;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\VisaModel\Command\SaveVisaModelCommand;
use App\Application\VisaModel\Query\GetVisaModelQuery;
use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Infrastructure\Controller\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\User\VisaModelFormType;
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

final class EditVisaModelController extends AbstractOrganizationController
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
        '/organizations/{organizationUuid}/visa_models/{uuid}/edit',
        name: 'app_config_visa_models_edit',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $organizationUuid, string $uuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $visaModel = $this->queryBus->handle(new GetVisaModelQuery($uuid));
        } catch (VisaModelNotFoundException) {
            throw new NotFoundHttpException();
        }

        $command = new SaveVisaModelCommand($organization, $visaModel);
        $form = $this->formFactory->create(VisaModelFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_visa_models_list', ['uuid' => $organizationUuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'organization/visa_model/form.html.twig',
                context: [
                    'organization' => $organization,
                    'visaModel' => $visaModel,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
