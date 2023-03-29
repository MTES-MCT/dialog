<?php

namespace App\Infrastructure\Controller\User;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\SaveOrganizationCommand;
use App\Application\Organization\Query\GetOrganizationByUuidQuery;
use App\Application\QueryBusInterface;
use App\Application\User\Command\SaveUserCommand;
use App\Application\User\Command\UpdateUserCommand;
use App\Application\User\Query\GetUserByUuidQuery;
use App\Infrastructure\Form\Organization\AddOrganizationFormType;
use App\Infrastructure\Form\User\AddUserFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;

class EditUserController
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
        '/user/{uuid}',
        name: 'app_user_edit',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods : ['GET','POST'],
    )]

    public function __invoke(Request $request,string $uuid) : Response 
    {
            $user = $this->queryBus->handle(
                new GetUserByUuidQuery($uuid)
            );
            
            if (!$user){
                throw new NotFoundHttpException();
            }
            $command = new UpdateUserCommand();
            $command->user=$user;
            $command->fullName=$user->getFullName();
            $command->email=$user->getEmail();
            $form = $this->formFactory->create(AddUserFormType::class, $command);
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
              $this->commandBus->handle($command);
              return new RedirectResponse(
                url: $this->router->generate('app_list_user'),
                status: Response::HTTP_SEE_OTHER,
            );
            }
            return new Response($this->twig->render(
                name: 'user/user_add.html.twig',
                context: [
                    'form'=> $form->createView(),
                ]
                )
            );
    }
}