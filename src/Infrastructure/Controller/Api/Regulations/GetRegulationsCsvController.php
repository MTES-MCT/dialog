<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForApiQuery;
use App\Application\Regulation\Query\GetRegulationOrdersForCsvExportQuery;
use App\Application\Regulation\RegulationExportCsvGeneratorInterface;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class GetRegulationsCsvController
{
    private const FILENAME = 'restrictions.csv';
    private const CONTENT_TYPE = 'text/csv; charset=UTF-8';

    private const ALLOWED_STATUSES = [
        GetRegulationOrdersForApiQuery::STATUS_CURRENT,
        GetRegulationOrdersForApiQuery::STATUS_EXPIRED,
        GetRegulationOrdersForApiQuery::STATUS_UPCOMING,
        GetRegulationOrdersForApiQuery::STATUS_ALL,
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly RegulationExportCsvGeneratorInterface $csvGenerator,
    ) {
    }

    #[Route(
        '/api/regulations/csv',
        name: 'api_regulations_csv',
        methods: ['GET', 'HEAD'],
        // Priorité supérieure à `api_regulations_get` (`/api/regulations/{identifier}`) dont
        // la contrainte `.+` capturerait sinon le segment `csv`.
        priority: 10,
    )]
    #[OA\Tag(name: 'Public')]
    #[OA\Get(
        summary: 'Exporter les restrictions de circulation au format CSV',
        description: <<<'DESCRIPTION'
            Exporte la base nationale des restrictions au format CSV. **Une ligne = une emprise**
            (localisation d'une mesure). Les colonnes exposent notamment l'UUID et le titre de l'arrêté,
            le lien vers le document source (PDF), ainsi que le type de restriction.

            ### Comportement par défaut
            Sans aucun filtre, l'export porte sur **toute la base publiée** (tous statuts confondus). Cet
            export complet est pré-généré et servi depuis un cache pour un téléchargement rapide.

            ### Filtres
            Les mêmes filtres que l'API JSON de recherche (`GET /api/regulations/json`) sont disponibles.
            Dès qu'un filtre est fourni, l'export est calculé à la volée.

            ### Authentification
            Cette route est publique : aucune authentification n'est requise.

            ### Format de la réponse
            Un fichier CSV encodé en UTF-8 (avec BOM), délimité par des points-virgules (`;`).
            DESCRIPTION,
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Statut de vigueur : `current` (en vigueur), `expired` (expiré), '
            . '`upcoming` (à venir) ou `all` (tous statuts confondus, défaut de l\'export).',
        schema: new OA\Schema(type: 'string', enum: ['current', 'expired', 'upcoming', 'all'], default: 'all'),
    )]
    #[OA\Parameter(
        name: 'inseeCode',
        in: 'query',
        required: false,
        description: "Code INSEE exact d'une commune.",
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'dateStart',
        in: 'query',
        required: false,
        description: 'Début de la plage des arrêtés en vigueur à cette date (ISO 8601).',
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
        description: "Nature de l'arrêté : `permanentRegulation` ou `temporaryRegulation`.",
        schema: new OA\Schema(type: 'string', enum: ['permanentRegulation', 'temporaryRegulation']),
    )]
    #[OA\Parameter(
        name: 'measureType',
        in: 'query',
        required: false,
        description: 'Type de restriction recherché.',
        schema: new OA\Schema(type: 'string', enum: ['alternateRoad', 'noOvertaking', 'noEntry', 'speedLimitation', 'parkingProhibited']),
    )]
    #[OA\Parameter(
        name: 'includeHeavyGoodsVehicle',
        in: 'query',
        required: false,
        description: 'Inclure les restrictions poids lourds. Défaut : `true`.',
        schema: new OA\Schema(type: 'boolean', default: true),
    )]
    #[OA\Response(
        response: 200,
        description: 'Fichier CSV des restrictions.',
        content: new OA\MediaType(mediaType: 'text/csv'),
    )]
    #[OA\Response(
        response: 400,
        description: 'Paramètre de filtre invalide.',
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter]
        string $status = GetRegulationOrdersForApiQuery::STATUS_ALL,
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
    ): Response {
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

        $headers = [
            'Content-Type' => self::CONTENT_TYPE,
            'Content-Disposition' => \sprintf('attachment; filename="%s"', self::FILENAME),
        ];

        $isFullExport = $status === GetRegulationOrdersForApiQuery::STATUS_ALL
            && $inseeCode === null
            && $parsedDateStart === null
            && $parsedDateEnd === null
            && $category === null
            && $measureType === null
            && $includeHeavyGoodsVehicle;

        // Export complet : servi depuis le cache pré-généré pour un téléchargement rapide.
        if ($isFullExport) {
            if ($request->isMethod('HEAD')) {
                return new Response('', Response::HTTP_OK, $headers + [
                    'Content-Length' => (string) $this->csvGenerator->getCachedCsvSize(),
                ]);
            }

            return new Response(
                $this->csvGenerator->getCachedCsv(),
                Response::HTTP_OK,
                $headers + ['Content-Length' => (string) $this->csvGenerator->getCachedCsvSize()],
            );
        }

        if ($request->isMethod('HEAD')) {
            return new Response('', Response::HTTP_OK, $headers);
        }

        /** @var \App\Application\Regulation\View\RegulationCsvRowView[] $rows */
        $rows = $this->queryBus->handle(
            new GetRegulationOrdersForCsvExportQuery(
                vigueurStatus: $status,
                inseeCode: $inseeCode,
                dateStart: $parsedDateStart,
                dateEnd: $parsedDateEnd,
                category: $category,
                measureType: $measureType,
                includeHeavyGoodsVehicle: $includeHeavyGoodsVehicle,
            ),
        );

        return new StreamedResponse(
            function () use ($rows): void {
                $handle = fopen('php://output', 'w');
                $this->csvGenerator->writeCsv($rows, $handle);
                fclose($handle);
            },
            Response::HTTP_OK,
            $headers,
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

    private function badRequest(string $message): Response
    {
        return new Response(
            json_encode(['error' => $message]),
            Response::HTTP_BAD_REQUEST,
            ['Content-Type' => 'application/json'],
        );
    }
}
