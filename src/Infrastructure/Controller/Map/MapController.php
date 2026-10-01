<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\DateUtilsInterface;
use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\AdministrativeBoundaryResolver;
use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Controller\AdministrativeBoundaryQueryParameters;
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
        private AdministrativeBoundaryResolver $administrativeBoundaryResolver,
        private AdministrativeBoundaryRepositoryInterface $administrativeBoundaryRepository,
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

        $submittedParams = $request->query->all($form->getName());
        if ($submittedParams !== [] && !\array_key_exists('statusFilterActive', $submittedParams)) {
            $submittedParams['displayPublished'] = 'yes';
            $submittedParams['statusFilterActive'] = '1';
            $request->query->set($form->getName(), $submittedParams);
        }

        $form->handleRequest($request);

        $user = $this->security->getUser();
        $userUuid = $user instanceof AbstractAuthenticatedUser ? $user->getUuid() : null;

        // Filtre par collectivité : les codes du COG sont passés dans l'URL (inseeCode, epciCode,
        // departmentCode, regionCode), à côté de organizationUuid et embed.
        $administrativeBoundaries = $this->resolveAdministrativeBoundaries($request);

        $initialBbox = match (true) {
            $regulationOrderRecordUuid !== null => $this->locationRepository->findMapBboxByRegulationOrderRecordUuid($regulationOrderRecordUuid->toString()),
            $administrativeBoundaries !== [] => $this->administrativeBoundaryRepository->findMapBbox($administrativeBoundaries[0]->codeType, $administrativeBoundaries[0]->code),
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
                    'administrativeBoundaries' => $administrativeBoundaries,
                    'administrativeBoundaryParameterNames' => AdministrativeBoundaryQueryParameters::NAMES,
                ],
            ),
        );
    }

    /**
     * @return AdministrativeBoundaryView[]
     */
    private function resolveAdministrativeBoundaries(Request $request): array
    {
        $boundaries = [];

        foreach (AdministrativeBoundaryQueryParameters::fromRequest($request) as $codeType => $code) {
            try {
                $boundaries[] = $this->administrativeBoundaryResolver->resolve(OrganizationCodeTypeEnum::from($codeType), $code);
            } catch (AdministrativeBoundaryNotFoundException|AdministrativeBoundaryUnavailableException) {
                // Collectivité inconnue ou contour indisponible : on affiche la carte sans ce filtre
                // plutôt qu'une page d'erreur (la carte peut être intégrée en iframe dans un site tiers).
            }
        }

        return $boundaries;
    }
}
