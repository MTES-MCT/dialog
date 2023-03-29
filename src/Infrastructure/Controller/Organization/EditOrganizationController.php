<?php

namespace App\Infrastructure\Controller\Organization;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\SaveOrganizationCommand;
use App\Application\Organization\Query\GetOrganizationByUuidQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Form\Organization\AddOrganizationFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;

class EditOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private FormFactoryInterface $formFactory,
    ){
    }

    #[Route(
        '/organization/{uuid}',
        name: 'app_organization_edit',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods : ['GET','POST'],
    )]
    public function __invoke(Request $request,string $uuid) : Response 
    {
            $organization = $this->queryBus->handle(
                new GetOrganizationByUuidQuery ($uuid)
            );
            // dd($organization);
            
            if (!$organization){
                throw new NotFoundHttpException();
            }
            $command = new SaveOrganizationCommand ();
            $command->name=$organization->getName();
            $command->organization= $organization;
            $form = $this->formFactory->create(AddOrganizationFormType::class, $command);
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
              $this->commandBus->handle($command);
              return new RedirectResponse(
                url: $this->router->generate('app_organization_list'),
                status: Response::HTTP_SEE_OTHER,
            );
            }
            return new Response($this->twig->render(
                name: 'organization/add_organization.html.twig',
                context: [
                    'form'=> $form->createView(),
                ]
                )
            );
    }
}