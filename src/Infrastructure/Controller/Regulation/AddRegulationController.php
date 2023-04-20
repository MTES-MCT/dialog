<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class AddRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private Security $security,
        private RouterInterface $router,
    ) {
    }

    #[Route(
        '/regulations/add',
        name: 'app_regulation_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: new SaveRegulationGeneralInfoCommand(),
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
