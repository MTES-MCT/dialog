<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves an authenticated user the locations of their own organizations' DRAFT regulation
 * orders as GeoJSON, so they can preview them on the map before publication.
 *
 * Unlike the published restrictions (served as public, CDN-cached MVT tiles), drafts are
 * private and organization-scoped: this endpoint lives behind the authenticated firewall
 * (see config/packages/security.yaml: `^/carte/drafts` requires ROLE_USER) and must never
 * be cached by a shared cache.
 */
final class MapDraftsDataController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private LocationRepositoryInterface $locationRepository,
        private DateUtilsInterface $dateUtils,
        private Security $security,
    ) {
    }

    #[Route(
        '/carte/drafts.geojson',
        name: 'app_carto_drafts_data',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        $organizationUuids = $user instanceof AbstractAuthenticatedUser ? $user->getUserOrganizationUuids() : [];

        if (!$organizationUuids) {
            return $this->emptyResponse();
        }

        $dto = new MapFilterDTO($this->dateUtils->getNow());
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'method' => 'GET',
                'csrf_protection' => false,
            ],
        );
        $form->handleRequest($request);

        $locationsAsGeoJson = $this->locationRepository->findAllForMapAsGeoJSON(
            $dto->displayPermanentRegulations,
            $dto->displayTemporaryRegulations,
            $dto->measureTypes,
            $dto->startDate,
            $dto->endDate,
            RegulationOrderRecordStatusEnum::DRAFT,
            $organizationUuids,
            $dto->displayHeavyGoodsVehicles,
        );

        return $this->jsonResponse($locationsAsGeoJson);
    }

    private function emptyResponse(): Response
    {
        return $this->jsonResponse('{"type":"FeatureCollection","features":[]}');
    }

    private function jsonResponse(string $geoJson): Response
    {
        return new Response(
            $geoJson,
            headers: [
                'Content-Type' => 'application/json',
                // Drafts are private and organization-scoped: never let a shared cache store them.
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
