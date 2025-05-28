<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\Organization\Logo\Command\SaveOrganizationLogoCommand;
use App\Application\QueryBusInterface;
use App\Application\StorageInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\Regulation\StorageRegulationOrderFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;

final class EditStorageRegulationOrderController extends AbstractOrganizationController
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
        '/regulations/{uuid}/storage/edit',
        name: 'app_config_regulation_edit_storage',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        // $command = new SaveOrganizationLogoCommand($organization);
        $form = $this->formFactory->create(StorageRegulationOrderFormType::class);
        $form->handleRequest($request);

        /* if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_config_organization_edit_logo', ['uuid' => $uuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        $logo = $organization->getLogo() ? $this->storage->getUrl($organization->getLogo()) : null; */

        return new Response(
            content: $this->twig->render(
                name: 'regulation/storage_regulation_order.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
