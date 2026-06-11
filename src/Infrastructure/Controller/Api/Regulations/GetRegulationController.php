<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\DTO\Regulation\RegulationApiView;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GetRegulationController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    #[Route(
        '/api/regulations/{identifier}',
        name: 'api_regulations_get',
        methods: ['GET'],
        requirements: ['identifier' => '.+'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\Get(
        summary: 'Récupérer un arrêté de circulation par son identifiant',
        description: <<<'DESCRIPTION'
            Retourne le détail complet d'un arrêté de circulation « regulation order » appartenant à
            l'organisation du client API authentifié.

            ### À quoi sert ce point d'accès ?
            Cette ressource permet à un système tiers de récupérer toutes les informations
            nécessaires pour afficher, exploiter ou rediffuser un arrêté : ses informations générales (titre,
            dates, catégorie), ses **mesures** (ce qui est réglementé) et leurs **localisations**
            (où la mesure s'applique) ainsi que leurs **périodes** d'application (quand la mesure s'applique).

            ### Authentification
            Cette route est privée : elle nécessite l'envoi des entêtes `X-Client-Id` et `X-Client-Secret`
            (clés API rattachées à une organisation). Seuls les arrêtés appartenant à cette organisation
            peuvent être lus, qu'ils soient en brouillon (`draft`) ou publiés (`published`).

            ### Paramètres
            - `identifier` (chemin) : identifiant métier de l'arrêté tel que défini par l'organisation
              (par ex. `F2025/001`). Les `/` sont autorisés.

            ### Format de la réponse
            Un objet JSON décrivant l'arrêté. Toutes les dates sont au format ISO 8601 (RFC 3339, par ex.
            `2025-10-09T08:00:00+00:00`). Le tableau `measures` contient zéro ou plusieurs mesures, chacune
            pouvant avoir plusieurs `locations` (segments de voirie concernés) et plusieurs `periods`
            (créneaux d'application).
            DESCRIPTION,
    )]
    #[OA\Parameter(
        name: 'identifier',
        in: 'path',
        required: true,
        description: "Identifiant métier de l'arrêté tel que saisi par l'organisation émettrice "
            . '(par ex. `F2025/001`). Les caractères `/` sont autorisés.',
        schema: new OA\Schema(type: 'string', example: 'F2025/001'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Arrêté trouvé : détail complet renvoyé.',
        content: new OA\JsonContent(
            type: 'object',
            description: "Représentation complète d'un arrêté de circulation.",
            properties: [
                new OA\Property(
                    property: 'identifier',
                    type: 'string',
                    description: "Identifiant métier de l'arrêté, unique au sein de l'organisation émettrice.",
                    example: 'F2025/001',
                ),
                new OA\Property(
                    property: 'status',
                    type: 'string',
                    enum: ['draft', 'published'],
                    description: "Statut de l'arrêté : `draft` (brouillon, non encore publié, "
                        . 'visible uniquement de son organisation) ou `published` (publié et opposable, '
                        . 'diffusé publiquement).',
                    example: 'draft',
                ),
                new OA\Property(
                    property: 'category',
                    type: 'string',
                    enum: ['permanentRegulation', 'temporaryRegulation'],
                    description: "Nature de l'arrêté : `permanentRegulation` (réglementation permanente, "
                        . 'sans date de fin) ou `temporaryRegulation` (réglementation temporaire, '
                        . 'avec une période de validité bornée par `startDate` et `endDate`).',
                    example: 'temporaryRegulation',
                ),
                new OA\Property(
                    property: 'subject',
                    type: 'string',
                    nullable: true,
                    description: "Motif de l'arrêté lorsqu'il est temporaire. Valeurs possibles : "
                        . '`roadMaintenance` (travaux de voirie), `incident`, `event` (manifestation), '
                        . '`winterMaintenance` (viabilité hivernale), `other`. `null` pour un arrêté permanent.',
                    example: 'roadMaintenance',
                ),
                new OA\Property(
                    property: 'otherCategoryText',
                    type: 'string',
                    nullable: true,
                    description: 'Texte libre précisant le motif lorsque `subject` vaut `other`. '
                        . '`null` dans tous les autres cas.',
                    example: null,
                ),
                new OA\Property(
                    property: 'title',
                    type: 'string',
                    description: "Titre lisible de l'arrêté tel que rédigé par l'organisation.",
                    example: 'Travaux de voirie rue Exemple',
                ),
                new OA\Property(
                    property: 'startDate',
                    type: 'string',
                    format: 'date-time',
                    nullable: true,
                    description: "Date et heure de début de validité de l'arrêté (ISO 8601). "
                        . 'Peut être `null` pour un arrêté permanent sans date de prise d\'effet renseignée.',
                    example: '2025-10-09T08:00:00+00:00',
                ),
                new OA\Property(
                    property: 'endDate',
                    type: 'string',
                    format: 'date-time',
                    nullable: true,
                    description: "Date et heure de fin de validité de l'arrêté (ISO 8601). "
                        . '`null` pour un arrêté permanent.',
                    example: '2025-10-15T18:00:00+00:00',
                ),
                new OA\Property(
                    property: 'organization',
                    type: 'object',
                    description: "Organisation (collectivité, gestionnaire de voirie, etc.) émettrice de l'arrêté.",
                    properties: [
                        new OA\Property(
                            property: 'uuid',
                            type: 'string',
                            nullable: true,
                            description: "Identifiant unique (UUID v4) de l'organisation dans DiaLog.",
                            example: '123e4567-e89b-12d3-a456-426614174000',
                        ),
                        new OA\Property(
                            property: 'name',
                            type: 'string',
                            description: "Nom officiel de l'organisation émettrice.",
                            example: 'Ma collectivité',
                        ),
                    ],
                ),
                new OA\Property(
                    property: 'measures',
                    type: 'array',
                    description: "Liste des mesures de l'arrêté. Une mesure décrit *ce qui est réglementé* "
                        . '(ex. limitation de vitesse à 30 km/h), *où* cela s\'applique (`locations`) et '
                        . '*quand* cela s\'applique (`periods`).',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'uuid',
                                type: 'string',
                                description: 'Identifiant unique (UUID v4) de la mesure.',
                                example: '123e4567-e89b-12d3-a456-426614174000',
                            ),
                            new OA\Property(
                                property: 'type',
                                type: 'string',
                                enum: ['alternateRoad', 'noOvertaking', 'noEntry', 'speedLimitation', 'parkingProhibited'],
                                description: 'Type de mesure : `alternateRoad` (circulation alternée), '
                                    . '`noOvertaking` (interdiction de dépasser), `noEntry` (interdiction de circuler), '
                                    . '`speedLimitation` (limitation de vitesse), `parkingProhibited` '
                                    . '(stationnement interdit).',
                                example: 'speedLimitation',
                            ),
                            new OA\Property(
                                property: 'maxSpeed',
                                type: 'integer',
                                nullable: true,
                                description: 'Vitesse maximale autorisée en km/h. Renseigné uniquement '
                                    . 'lorsque `type` vaut `speedLimitation`, `null` sinon.',
                                example: 30,
                            ),
                            new OA\Property(
                                property: 'vehicleSet',
                                type: 'object',
                                nullable: true,
                                description: 'Catégories de véhicules concernées par la mesure. `null` si '
                                    . 'la mesure s\'applique à tous les véhicules sans distinction.',
                                properties: [
                                    new OA\Property(
                                        property: 'restrictedTypes',
                                        type: 'array',
                                        description: 'Types de véhicules visés par la restriction '
                                            . '(ex. `heavyGoodsVehicle`, `bus`, `motorcycle`...).',
                                        items: new OA\Items(type: 'string'),
                                    ),
                                    new OA\Property(
                                        property: 'exemptedTypes',
                                        type: 'array',
                                        description: 'Types de véhicules explicitement exemptés '
                                            . '(non concernés par la restriction).',
                                        items: new OA\Items(type: 'string'),
                                    ),
                                    new OA\Property(
                                        property: 'maxCharacteristics',
                                        type: 'array',
                                        description: 'Caractéristiques physiques maximales pour qu\'un '
                                            . 'véhicule soit concerné (ex. tonnage, longueur, hauteur).',
                                        items: new OA\Items(type: 'object'),
                                    ),
                                ],
                            ),
                            new OA\Property(
                                property: 'periods',
                                type: 'array',
                                description: 'Créneaux temporels d\'application de la mesure. En l\'absence '
                                    . 'de période, la mesure s\'applique sur toute la durée de validité de '
                                    . 'l\'arrêté (`startDate` → `endDate`).',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'recurrenceType',
                                            type: 'string',
                                            enum: ['everyDay', 'certainDays'],
                                            description: 'Mode de récurrence : `everyDay` (tous les jours '
                                                . 'sur la plage `startDateTime` → `endDateTime`) ou '
                                                . '`certainDays` (uniquement certains jours de la semaine, '
                                                . 'voir `dailyRange`).',
                                            example: 'everyDay',
                                        ),
                                        new OA\Property(
                                            property: 'startDateTime',
                                            type: 'string',
                                            format: 'date-time',
                                            nullable: true,
                                            description: 'Date et heure de début de la période (ISO 8601).',
                                        ),
                                        new OA\Property(
                                            property: 'endDateTime',
                                            type: 'string',
                                            format: 'date-time',
                                            nullable: true,
                                            description: 'Date et heure de fin de la période (ISO 8601).',
                                        ),
                                        new OA\Property(
                                            property: 'dailyRange',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Restriction aux jours de la semaine concernés '
                                                . 'lorsque `recurrenceType` vaut `certainDays`.',
                                            properties: [
                                                new OA\Property(
                                                    property: 'dayRanges',
                                                    type: 'array',
                                                    description: 'Liste des jours actifs '
                                                        . '(ex. `monday`, `tuesday`, ...).',
                                                    items: new OA\Items(type: 'string'),
                                                ),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'timeSlots',
                                            type: 'array',
                                            description: 'Créneaux horaires journaliers d\'application '
                                                . '(ex. 08:00 → 18:00). Vide si la mesure s\'applique '
                                                . '24h/24 sur les jours retenus.',
                                            items: new OA\Items(
                                                type: 'object',
                                                properties: [
                                                    new OA\Property(property: 'startTime', type: 'string', format: 'date-time', nullable: true),
                                                    new OA\Property(property: 'endTime', type: 'string', format: 'date-time', nullable: true),
                                                ],
                                            ),
                                        ),
                                    ],
                                ),
                            ),
                            new OA\Property(
                                property: 'locations',
                                type: 'array',
                                description: 'Emplacements géographiques sur lesquels la mesure s\'applique. '
                                    . 'Chaque emplacement est décrit selon son type de voirie (`roadType`) ; '
                                    . 'un seul des champs `namedStreet`, `numberedRoad`, `rawGeoJSON` ou '
                                    . '`storageArea` est renseigné, les autres valent `null`.',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'uuid',
                                            type: 'string',
                                            description: 'Identifiant unique (UUID v4) de la localisation.',
                                        ),
                                        new OA\Property(
                                            property: 'roadType',
                                            type: 'string',
                                            enum: ['lane', 'departmentalRoad', 'nationalRoad', 'rawGeoJSON'],
                                            description: 'Type de voirie : `lane` (voie nommée en milieu '
                                                . 'urbain), `departmentalRoad` (route départementale), '
                                                . '`nationalRoad` (route nationale), `rawGeoJSON` '
                                                . '(géométrie GeoJSON brute fournie par l\'organisation).',
                                            example: 'lane',
                                        ),
                                        new OA\Property(
                                            property: 'namedStreet',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Voie nommée (rues, places, etc.). Renseigné '
                                                . 'lorsque `roadType` vaut `lane`.',
                                            properties: [
                                                new OA\Property(property: 'cityLabel', type: 'string', nullable: true, description: 'Commune de la voie.'),
                                                new OA\Property(property: 'roadName', type: 'string', nullable: true, description: 'Nom de la voie.'),
                                                new OA\Property(property: 'fromHouseNumber', type: 'string', nullable: true, description: 'Numéro de début (par ex. `12`, `12bis`).'),
                                                new OA\Property(property: 'fromRoadName', type: 'string', nullable: true, description: 'Nom de la voie transversale marquant le début (alternative à `fromHouseNumber`).'),
                                                new OA\Property(property: 'toHouseNumber', type: 'string', nullable: true, description: 'Numéro de fin.'),
                                                new OA\Property(property: 'toRoadName', type: 'string', nullable: true, description: 'Nom de la voie transversale marquant la fin.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'numberedRoad',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Route numérotée. Renseigné lorsque `roadType` '
                                                . 'vaut `departmentalRoad` ou `nationalRoad`.',
                                            properties: [
                                                new OA\Property(property: 'administrator', type: 'string', nullable: true, description: 'Gestionnaire de la route.'),
                                                new OA\Property(property: 'roadNumber', type: 'string', nullable: true, description: 'Numéro de la route (ex. `D7`, `N118`).'),
                                                new OA\Property(property: 'fromPointNumber', type: 'string', nullable: true, description: 'Point de repère (PR) de début.'),
                                                new OA\Property(property: 'fromAbscissa', type: 'integer', nullable: true, description: 'Abscisse en mètres depuis le PR de début.'),
                                                new OA\Property(property: 'fromSide', type: 'string', nullable: true, description: 'Côté du PR de début (ex. `U` amont, `D` aval).'),
                                                new OA\Property(property: 'toPointNumber', type: 'string', nullable: true, description: 'Point de repère (PR) de fin.'),
                                                new OA\Property(property: 'toAbscissa', type: 'integer', nullable: true, description: 'Abscisse en mètres depuis le PR de fin.'),
                                                new OA\Property(property: 'toSide', type: 'string', nullable: true, description: 'Côté du PR de fin.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'rawGeoJSON',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Géométrie brute fournie par l\'organisation '
                                                . 'lorsque `roadType` vaut `rawGeoJSON` (la géométrie '
                                                . 'effective est exposée par le champ `geometry`).',
                                            properties: [
                                                new OA\Property(property: 'label', type: 'string', description: 'Libellé descriptif de la zone.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'storageArea',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Aire de stockage associée (utilisée pour les '
                                                . 'arrêtés de circulation alternée notamment).',
                                            properties: [
                                                new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Description libre de l\'aire de stockage.'),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'geometry',
                                            type: 'string',
                                            nullable: true,
                                            description: 'Géométrie effective de la localisation, '
                                                . 'sérialisée en GeoJSON (chaîne JSON). Permet à un '
                                                . 'consommateur d\'afficher la zone sur une carte sans '
                                                . 'avoir à résoudre lui-même les références de voirie.',
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Authentification manquante ou invalide. '
            . 'Vérifier les entêtes `X-Client-Id` et `X-Client-Secret`.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: "Aucun arrêté avec cet `identifier` n'existe pour l'organisation du client API authentifié.",
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'detail', type: 'string', example: 'Not Found'),
            ],
        ),
    )]
    public function __invoke(string $identifier): JsonResponse
    {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();

        try {
            /** @var RegulationOrderRecord $regulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(
                new GetRegulationOrderRecordByIdentifierQuery($identifier, $user->getOrganization()),
            );
        } catch (RegulationOrderRecordNotFoundException) {
            return new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var GeneralInfoView $generalInfo */
        $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($regulationOrderRecord->getUuid()));

        /** @var MeasureView[] $measures */
        $measures = $this->queryBus->handle(new GetMeasuresQuery($regulationOrderRecord->getUuid()));

        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        return new JsonResponse(
            $this->normalizer->normalize(
                RegulationApiView::fromViews($generalInfo, $measures),
                'json',
                [DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM],
            ),
            Response::HTTP_OK,
        );
    }
}
