<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\DateUtilsInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private DateUtilsInterface $dateUtils,
        private Security $security,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    #[Route(
        '/carte',
        name: 'app_carto',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $dto = new MapFilterDTO($this->dateUtils->getNow());

        // The URL template contains literal `{z}/{x}/{y}` placeholders (with curly braces)
        // expanded by MapLibre on the client side. Symfony's URL generator does not allow
        // emitting parameters that don't match the route requirements (digits), so we build
        // the URL template manually using a placeholder that we then substitute.
        $placeholderUrl = $this->router->generate('app_carto_tiles', [
            'z' => '0',
            'x' => '0',
            'y' => '0',
        ]);
        $tilesUrlTemplate = preg_replace('#/0/0/0\.mvt$#', '/{z}/{x}/{y}.mvt', $placeholderUrl);

        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $tilesUrlTemplate,
                'method' => 'GET',
            ],
        );

        $user = $this->security->getUser();
        $userUuid = $user instanceof AbstractAuthenticatedUser ? $user->getUuid() : null;
        $initialBbox = $this->organizationRepository->findInitialMapBbox($userUuid);

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'form' => $form->createView(),
                    'tilesUrlTemplate' => $tilesUrlTemplate,
                    'initialBbox' => $initialBbox,
                ],
            ),
        );
    }
}
