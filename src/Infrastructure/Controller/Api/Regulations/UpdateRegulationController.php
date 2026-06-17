<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\GenerateDatexCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\UpdateRegulationWithMeasureCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\DTO\Event\RegulationGeneralInfoDTO;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UpdateRegulationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route(
        '/api/regulations/{identifier}',
        name: 'api_regulations_update',
        methods: ['PUT'],
        requirements: ['identifier' => '.+'],
    )]
    #[IsGranted('ROLE_API')]
    #[OA\Tag(name: 'Private')]
    #[OA\Put(
        summary: 'Mettre à jour un arrêté de circulation par son identifiant',
        description: <<<'DESCRIPTION'
            Remplace intégralement un arrêté de circulation « regulation order » appartenant à
            l'organisation du client API authentifié.

            ### À quoi sert ce point d'accès ?
            Cette ressource permet à un système tiers de mettre à jour un arrêté existant : ses
            informations générales (identifiant, titre, catégorie, motif) ainsi que ses **mesures**.

            ### Sémantique PUT (remplacement complet)
            Le payload attendu est identique à celui de la création (`POST /api/regulations`).
            Les **mesures existantes de l'arrêté sont supprimées puis recréées** à partir du tableau
            `measures` fourni. Un tableau `measures` vide ou absent supprime donc toutes les mesures.

            ### Statut
            - Si l'arrêté est en brouillon (`draft`) et que le payload indique `published`, l'arrêté est publié.
            - Un arrêté publié ne peut pas être repassé en brouillon : une requête avec `status: draft`
              sur un arrêté publié renvoie une erreur `400`.

            ### Authentification
            Cette route est privée : elle nécessite l'envoi des entêtes `X-Client-Id` et `X-Client-Secret`
            (clés API rattachées à une organisation). Seuls les arrêtés appartenant à cette organisation
            peuvent être modifiés.

            ### Paramètres
            - `identifier` (chemin) : identifiant métier actuel de l'arrêté tel que défini par l'organisation
              (par ex. `F2025/001`). Les `/` sont autorisés. L'identifiant peut être modifié en fournissant
              une nouvelle valeur dans le champ `identifier` du payload.
            DESCRIPTION,
    )]
    #[OA\Parameter(
        name: 'identifier',
        in: 'path',
        required: true,
        description: "Identifiant métier actuel de l'arrêté tel que saisi par l'organisation émettrice "
            . '(par ex. `F2025/001`). Les caractères `/` sont autorisés.',
        schema: new OA\Schema(type: 'string', example: 'F2025/001'),
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', maxLength: 60, example: 'F2025/001'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
                new OA\Property(property: 'category', type: 'string', enum: ['temporaryRegulation', 'permanentRegulation'], example: 'temporaryRegulation'),
                new OA\Property(property: 'subject', type: 'string', enum: ['roadMaintenance', 'incident', 'event', 'winterMaintenance', 'other'], nullable: true, example: 'roadMaintenance'),
                new OA\Property(property: 'otherCategoryText', type: 'string', nullable: true, maxLength: 100, example: null),
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Travaux de voirie rue Exemple'),
                new OA\Property(
                    property: 'measures',
                    type: 'array',
                    nullable: false,
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['alternateRoad', 'noEntry', 'speedLimitation', 'parkingProhibited', 'noOvertaking'], example: 'speedLimitation'),
                            new OA\Property(property: 'maxSpeed', type: 'integer', nullable: true, example: 30),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', nullable: true, example: '2025-10-09T08:00:00Z'),
                            new OA\Property(
                                property: 'vehicleSet',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'allVehicles', type: 'boolean', nullable: true, example: false),
                                    new OA\Property(property: 'restrictedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['heavyGoodsVehicle', 'dimensions', 'critair', 'hazardousMaterials', 'other']), nullable: true),
                                    new OA\Property(property: 'exemptedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['commercial', 'emergencyServices', 'bicycle', 'pedestrians', 'taxi', 'carSharing', 'roadMaintenanceOrConstruction', 'cityLogistics', 'police', 'desserteLocale', 'localResident', 'other']), nullable: true),
                                    new OA\Property(property: 'otherRestrictedTypeText', type: 'string', nullable: true),
                                    new OA\Property(property: 'otherExemptedTypeText', type: 'string', nullable: true),
                                    new OA\Property(property: 'heavyweightMaxWeight', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxWidth', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxLength', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'maxHeight', type: 'number', format: 'float', nullable: true),
                                    new OA\Property(property: 'critairTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['critair2', 'critair3', 'critair4', 'critair5']), nullable: true),
                                ],
                            ),
                            new OA\Property(
                                property: 'periods',
                                type: 'array',
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    required: ['startDate', 'recurrenceType'],
                                    properties: [
                                        new OA\Property(property: 'startDate', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'startTime', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'endDate', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'endTime', type: 'string', format: 'date-time', nullable: true, description: 'Obligatoire si isPermanent = false'),
                                        new OA\Property(property: 'recurrenceType', type: 'string', enum: ['everyDay', 'certainDays']),
                                        new OA\Property(property: 'isPermanent', type: 'boolean', nullable: true),
                                        new OA\Property(
                                            property: 'dailyRange',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'recurrenceType', type: 'string', enum: ['everyDay', 'certainDays'], nullable: true),
                                                new OA\Property(property: 'applicableDays', type: 'array', items: new OA\Items(type: 'string', enum: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']), nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'timeSlots',
                                            type: 'array',
                                            nullable: true,
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
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'roadType', type: 'string', enum: ['lane', 'departmentalRoad', 'nationalRoad', 'rawGeoJSON'], example: 'lane'),
                                        new OA\Property(
                                            property: 'namedStreet',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'cityCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'cityLabel', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointType', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromHouseNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromRoadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointType', type: 'string', nullable: true),
                                                new OA\Property(property: 'toHouseNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toRoadName', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'departmentalRoad',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'administrator', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'fromSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'toDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'toSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'nationalRoad',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'administrator', type: 'string', nullable: true),
                                                new OA\Property(property: 'roadNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'fromAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'fromSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'toDepartmentCode', type: 'string', nullable: true),
                                                new OA\Property(property: 'toPointNumber', type: 'string', nullable: true),
                                                new OA\Property(property: 'toAbscissa', type: 'integer', nullable: true),
                                                new OA\Property(property: 'toSide', type: 'string', nullable: true),
                                                new OA\Property(property: 'direction', type: 'string', enum: ['BOTH', 'A_TO_B', 'B_TO_A'], nullable: true),
                                            ],
                                        ),
                                        new OA\Property(
                                            property: 'rawGeoJSON',
                                            type: 'object',
                                            nullable: true,
                                            properties: [
                                                new OA\Property(property: 'label', type: 'string', nullable: true),
                                                new OA\Property(property: 'geometry', type: 'string', nullable: true),
                                            ],
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
        response: 200,
        description: 'Mise à jour réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                new OA\Property(property: 'identifier', type: 'string', example: 'F2025/001'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
            ],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur métier (géocodage / périmètre géographique / changement de statut invalide)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 400),
                new OA\Property(property: 'detail', type: 'string', example: 'Le géocodage de la voie a échoué.'),
            ],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié / identifiants client invalides',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Arrêté introuvable',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'detail', type: 'string', example: 'Not Found'),
            ],
        ),
    )]
    #[OA\Response(
        response: 422,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 422),
                new OA\Property(property: 'detail', type: 'string', example: 'Validation failed'),
                new OA\Property(
                    property: 'violations',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'propertyPath', type: 'string', example: 'locations[0].organization'),
                            new OA\Property(property: 'title', type: 'string', example: 'Cette valeur ne doit pas être vide.'),
                            new OA\Property(property: 'parameters', type: 'object'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(
        string $identifier,
        #[MapRequestPayload] RegulationGeneralInfoDTO $dto,
    ): JsonResponse {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();
        $organization = $user->getOrganization();

        try {
            /** @var RegulationOrderRecord $regulationOrderRecord */
            $regulationOrderRecord = $this->queryBus->handle(
                new GetRegulationOrderRecordByIdentifierQuery($identifier, $organization),
            );
        } catch (RegulationOrderRecordNotFoundException) {
            return new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => \sprintf('Aucun arrêté de circulation trouvé pour l\'identifiant %s au sein de l\'organisation %s.', $identifier, $organization->getName()),
            ], Response::HTTP_NOT_FOUND);
        }

        if ($dto->status === RegulationOrderRecordStatusEnum::DRAFT && !$regulationOrderRecord->isDraft()) {
            return new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'detail' => 'Un arrêté publié ne peut pas être repassé en brouillon.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $generalInfo = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);
        $this->objectMapper->map($dto, $generalInfo);

        $this->commandBus->handle(new UpdateRegulationWithMeasureCommand($generalInfo, $dto->status, $dto->measures));
        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        if (!$regulationOrderRecord->isDraft()) {
            $this->commandBus->dispatchAsync(new GenerateDatexCommand());
        }

        return new JsonResponse([
            'uuid' => $regulationOrderRecord->getUuid(),
            'identifier' => $regulationOrderRecord->getRegulationOrder()->getIdentifier(),
            'status' => $regulationOrderRecord->getStatus(),
        ], Response::HTTP_OK);
    }
}
