<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private DateUtilsInterface $dateUtils,
        private Security $security,
        private OrganizationRepositoryInterface $organizationRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/carte',
        name: 'app_carto',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] ?Uuid $organizationUuid,
        #[MapQueryParameter] ?Uuid $regulationOrderRecordUuid,
    ): Response {
        $dto = new MapFilterDTO($this->dateUtils->getNow());

        // The URL template contains literal `{z}/{x}/{y}` placeholders (with curly braces)
        // expanded by MapLibre on the client side. Symfony's URL generator does not allow
        // emitting parameters that don't match the route requirements (digits), so we build
        // the URL template manually.
        $tilesUrl = '/carte/tiles/{z}/{x}/{y}.mvt';

        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $tilesUrl,
                'method' => 'GET',
                'csrf_protection' => false,
            ],
        );

        // Date de début par défaut (aujourd'hui), telle qu'elle apparaît dans l'URL de la carte.
        $defaultStartDate = $form->get('startDate')->getViewData();

        $submittedParams = $request->query->all($form->getName());
        if ($submittedParams !== [] && !\array_key_exists('statusFilterActive', $submittedParams)) {
            $submittedParams['displayPublished'] = 'yes';
            $submittedParams['statusFilterActive'] = '1';
            $request->query->set($form->getName(), $submittedParams);
        }

        // Le code d'intégration ne fige pas la date de début par défaut : en mode intégré, une date de
        // début absente signifie « à partir d'aujourd'hui » (une date volontairement vidée est transmise vide).
        // Sans cela, la carte intégrée dans un site tiers resterait bloquée au jour où le code a été copié.
        if ($submittedParams !== [] && $request->query->get('embed') && !\array_key_exists('startDate', $submittedParams)) {
            $submittedParams['startDate'] = $defaultStartDate;
            $request->query->set($form->getName(), $submittedParams);
        }

        $form->handleRequest($request);

        $user = $this->security->getUser();
        $userUuid = $user instanceof AbstractAuthenticatedUser ? $user->getUuid() : null;

        $initialBbox = match (true) {
            $regulationOrderRecordUuid !== null => $this->locationRepository->findMapBboxByRegulationOrderRecordUuid($regulationOrderRecordUuid->toString()),
            $organizationUuid !== null => $this->organizationRepository->findMapBboxByOrganizationUuid($organizationUuid->toString()),
            default => $this->organizationRepository->findInitialMapBbox($userUuid),
        };

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'form' => $form->createView(),
                    'tilesUrlTemplate' => $tilesUrl,
                    'initialBbox' => $initialBbox,
                    'defaultStartDate' => $defaultStartDate,
                ],
            ),
        );
    }
}
