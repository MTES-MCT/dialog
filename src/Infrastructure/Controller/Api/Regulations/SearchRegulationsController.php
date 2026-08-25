<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForApiQuery;
use App\Domain\Pagination;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Infrastructure\DTO\Regulation\RegulationApiView;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SearchRegulationsController
{
    private const MAX_PAGE_SIZE = 100;

    private const ALLOWED_STATUSES = [
        GetRegulationOrdersForApiQuery::STATUS_CURRENT,
        GetRegulationOrdersForApiQuery::STATUS_EXPIRED,
        GetRegulationOrdersForApiQuery::STATUS_UPCOMING,
        GetRegulationOrdersForApiQuery::STATUS_ALL,
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    #[Route(
        '/api/regulations/json',
        name: 'api_regulations_json',
        methods: ['GET'],
        // Priorité supérieure à `api_regulations_get` (`/api/regulations/{identifier}`) dont
        // la contrainte `.+` capturerait sinon le segment `json`.
        priority: 10,
    )]
    #[OA\Tag(name: 'Public')]
    #[OA\Get(
        summary: 'Rechercher les arrêtés de circulation et leurs restrictions',
        description: <<<'DESCRIPTION'
            Retourne la liste des arrêtés de circulation « regulation orders » **publiés** de toutes les
            organisations, accompagnés de leurs mesures (restrictions) et de leurs emprises.

            Un arrêté est constitué de plusieurs **mesures** (ce qui est réglementé), chaque mesure
            portant une ou plusieurs **emprises** (`locations`, où la mesure s'applique) et une ou
            plusieurs **périodes** (`periods`, quand la mesure s'applique).

            ### Comportement par défaut
            Sans aucun filtre, seuls les arrêtés **en vigueur** (`status=current`) sont retournés.

            ### Authentification
            Cette route est publique : aucune authentification n'est requise.

            ### Format de la réponse
            Un objet JSON contenant un tableau `regulations` (chaque arrêté ayant la même structure que
            `GET /api/regulations/{identifier}`) et un objet `metadata` de pagination. Toutes les dates
            sont au format ISO 8601 (RFC 3339).
            DESCRIPTION,
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Statut de vigueur : `current` (en vigueur, défaut), `expired` (expiré), '
            . '`upcoming` (à venir) ou `all` (tous statuts confondus).',
        schema: new OA\Schema(type: 'string', enum: ['current', 'expired', 'upcoming', 'all'], default: 'current'),
    )]
    #[OA\Parameter(
        name: 'inseeCode',
        in: 'query',
        required: false,
        description: "Code INSEE exact d'une commune. Ne retourne que les arrêtés dont au moins une "
            . 'emprise concerne cette commune (voie nommée ou ville entière).',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'dateStart',
        in: 'query',
        required: false,
        description: 'Début de la plage des arrêtés en vigueur à cette date (ISO 8601). Ne retourne que les arrêtés '
            . 'dont la validité chevauche cette plage.',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01'),
    )]
    #[OA\Parameter(
        name: 'dateEnd',
        in: 'query',
        required: false,
        description: 'Fin de la plage de dates de vigueur (ISO 8601).',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31'),
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query',
        required: false,
        description: "Nature de l'arrêté : `permanentRegulation` (permanent) ou `temporaryRegulation` "
            . '(temporaire).',
        schema: new OA\Schema(type: 'string', enum: ['permanentRegulation', 'temporaryRegulation']),
    )]
    #[OA\Parameter(
        name: 'measureType',
        in: 'query',
        required: false,
        description: 'Type de restriction recherché. Ne retourne que les arrêtés comportant au moins '
            . 'une mesure de ce type.',
        schema: new OA\Schema(type: 'string', enum: ['alternateRoad', 'noOvertaking', 'noEntry', 'speedLimitation', 'parkingProhibited']),
    )]
    #[OA\Parameter(
        name: 'includeHeavyGoodsVehicle',
        in: 'query',
        required: false,
        description: 'Inclure les restrictions poids lourds. Si `false`, exclut les arrêtés dont au '
            . 'moins une mesure restreint les poids lourds. Défaut : `true`.',
        schema: new OA\Schema(type: 'boolean', default: true),
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'Numéro de page (à partir de 1).',
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
    )]
    #[OA\Parameter(
        name: 'pageSize',
        in: 'query',
        required: false,
        description: "Nombre d'arrêtés par page (max 100).",
        schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100),
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste paginée des arrêtés correspondant aux filtres.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'metadata',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'pageSize', type: 'integer', example: 20),
                        new OA\Property(property: 'totalItems', type: 'integer', example: 42),
                        new OA\Property(property: 'lastPage', type: 'integer', example: 3),
                    ],
                ),
                new OA\Property(
                    property: 'regulations',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        description: "Représentation complète d'un arrêté (voir GET /api/regulations/{identifier}).",
                    ),
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Paramètre de filtre invalide.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid "status" parameter'),
            ],
        ),
    )]
    public function __invoke(
        #[MapQueryParameter]
        string $status = GetRegulationOrdersForApiQuery::STATUS_CURRENT,
        #[MapQueryParameter]
        ?string $inseeCode = null,
        #[MapQueryParameter]
        ?string $dateStart = null,
        #[MapQueryParameter]
        ?string $dateEnd = null,
        #[MapQueryParameter]
        ?string $category = null,
        #[MapQueryParameter]
        ?string $measureType = null,
        #[MapQueryParameter]
        bool $includeHeavyGoodsVehicle = true,
        #[MapQueryParameter]
        int $page = 1,
        #[MapQueryParameter]
        int $pageSize = 20,
    ): JsonResponse {
        if (!\in_array($status, self::ALLOWED_STATUSES, true)) {
            return $this->badRequest('Invalid "status" parameter');
        }

        if ($category !== null && !\in_array($category, array_column(RegulationOrderCategoryEnum::cases(), 'value'), true)) {
            return $this->badRequest('Invalid "category" parameter');
        }

        if ($measureType !== null && !\in_array($measureType, array_column(MeasureTypeEnum::cases(), 'value'), true)) {
            return $this->badRequest('Invalid "measureType" parameter');
        }

        $parsedDateStart = $this->parseDate($dateStart);
        if ($dateStart !== null && $parsedDateStart === null) {
            return $this->badRequest('Invalid "dateStart" parameter');
        }

        $parsedDateEnd = $this->parseDate($dateEnd);
        if ($dateEnd !== null && $parsedDateEnd === null) {
            return $this->badRequest('Invalid "dateEnd" parameter');
        }

        $page = max(1, $page);
        $pageSize = min(max(1, $pageSize), self::MAX_PAGE_SIZE);

        /** @var Pagination $pagination */
        $pagination = $this->queryBus->handle(
            new GetRegulationOrdersForApiQuery(
                vigueurStatus: $status,
                inseeCode: $inseeCode,
                dateStart: $parsedDateStart,
                dateEnd: $parsedDateEnd,
                category: $category,
                measureType: $measureType,
                includeHeavyGoodsVehicle: $includeHeavyGoodsVehicle,
                page: $page,
                pageSize: $pageSize,
            ),
        );

        $regulations = $this->normalizer->normalize(
            array_map(RegulationApiView::fromApiView(...), $pagination->items),
            'json',
            [DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM],
        );

        return new JsonResponse(
            [
                'metadata' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalItems' => $pagination->totalItems,
                    'lastPage' => $pagination->lastPage,
                ],
                'regulations' => $regulations,
            ],
            Response::HTTP_OK,
        );
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    private function badRequest(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
    }
}
