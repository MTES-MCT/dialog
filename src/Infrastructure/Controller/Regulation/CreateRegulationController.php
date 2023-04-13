<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class CreateRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private Security $security,
        private RouterInterface $router,
    ) {
    }

    #[Route(
        '/regulations/form',
        name: 'app_regulation_create',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: new SaveRegulationOrderCommand(),
            options: [
                'organizations' => [$user->getOrganization()],
                'action' => $this->router->generate('fragment_regulations_general_info_form'),
            ],
        );

        return new Response(
            $this->twig->render(
                name: 'regulation/create.html.twig',
                context: ['form' => $form->createView()],
            ),
        );
    }
}
