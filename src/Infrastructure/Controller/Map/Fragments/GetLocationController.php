<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetLocationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private Security $security,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
    }

    #[Route(
        '/map/fragments/location/{uuid}',
        name: 'fragment_map_location',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid = ''): Response
    {
        $location = $this->queryBus->handle(new GetLocationByUuidQuery($uuid));
        if (!$location instanceof Location) {
            throw new NotFoundHttpException();
        }

        $measure = $location->getMeasure();
        $regulation = $measure->getRegulationOrder();
        $regulationOrderRecord = $regulation->getRegulationOrderRecord();

        // Draft regulations are private: only members of the owning organization may preview
        // their locations on the map. For anyone else (anonymous or another organization), behave
        // as if the location did not exist so we don't disclose the draft's existence.
        if ($regulationOrderRecord->getStatus() === RegulationOrderRecordStatusEnum::DRAFT->value) {
            $user = $this->security->getUser();
            $organizationUuids = $user instanceof AbstractAuthenticatedUser ? $user->getUserOrganizationUuids() : [];

            if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $organizationUuids)) {
                throw new NotFoundHttpException();
            }
        }

        $measureView = MeasureView::fromEntity($measure);
        $regulationOrderRecordId = $regulationOrderRecord->getUuid();

        $locationView = null;
        foreach ($measureView->locations as $candidate) {
            if ($candidate->uuid === $location->getUuid()) {
                $locationView = $candidate;
                break;
            }
        }

        return new Response(
            $this->twig->render(
                name: 'map/fragments/location_popup.html.twig',
                context: [
                    'location' => $locationView ?? $location,
                    'measure' => $measureView,
                    'regulation' => $regulation,
                    'regulationOrderRecordId' => $regulationOrderRecordId,
                ],
            ),
        );
    }
}
