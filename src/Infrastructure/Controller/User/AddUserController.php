<?php

namespace App\Infrastructure\Controller\User;

use App\Application\CommandBusInterface;
use App\Application\User\Command\SaveUserCommand;
use App\Domain\User\Exception\UserAlreadyExistException;
use App\Infrastructure\Form\User\AddUserFormType;
use Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class AddUserController{

    public function __construct(
        private FormFactoryInterface $formFactoryInterface,
        private \Twig\Environment $twig,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
    ){
}

    #[Route(
        '/user/add',
        name: 'app_user_add',
        methods: ['GET','POST'],
    )]
    
    public function __invoke(Request $request) : Response
    {
        $command = new SaveUserCommand();
        $form = $this->formFactoryInterface->create(AddUserFormType::class,$command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try{
                $this->commandBus->handle($command);
                return new RedirectResponse(
                url: $this->router->generate('app_list_user'),
                status: Response::HTTP_SEE_OTHER,
                );
            } catch(UserAlreadyExistException) {
                /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('error', 'l\'utilisateur existe déjà');
            }
        }

        return new Response($this->twig->render(
            name: 'user/user_add.html.twig',
            context: [
                'form'=> $form->createView(),
            ]
            ), status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}