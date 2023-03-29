<?php

namespace
App\Infrastructure\Controller\User;

use App\Application\QueryBusInterface;
use App\Application\User\Query\GetUsersQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListUserController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private readonly TranslatorInterface $translator,
    ){
    }

    #[Route(
        '/users',
        name: 'app_list_user',
        methods: ['GET'],
    )]
    public function __invoke(){
        $users=$this->queryBus->handle(new GetUsersQuery());
        return new Response($this->twig->render(
            name: 'user/index.html.twig',
            context:['users'=>$users],
        ));
    }
}