<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationWithMeasureCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Infrastructure\DTO\Event\RegulationGeneralInfoDTO;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AddRegulationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route(
        '/api/regulations',
        name: 'api_regulations_add',
        methods: ['POST'],
    )]
    #[OA\Tag(name: 'Private')]
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
                            new OA\Property(property: 'type', type: 'string', enum: ['alternateRoad', 'noEntry', 'speedLimitation', 'parkingProhibited'], example: 'speedLimitation'),
                            new OA\Property(property: 'maxSpeed', type: 'integer', nullable: true, example: 30),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', nullable: true, example: '2025-10-09T08:00:00Z'),
                            new OA\Property(
                                property: 'vehicleSet',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'allVehicles', type: 'boolean', nullable: true, example: false),
                                    new OA\Property(property: 'restrictedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['heavyGoodsVehicle', 'dimensions', 'critair', 'hazardousMaterials', 'other']), nullable: true),
                                    new OA\Property(property: 'exemptedTypes', type: 'array', items: new OA\Items(type: 'string', enum: ['commercial', 'emergencyServices', 'bicycle', 'pedestrians', 'taxi', 'carSharing', 'roadMaintenanceOrConstruction', 'cityLogistics', 'other']), nullable: true),
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
        response: 201,
        description: 'Création réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
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
        response: 400,
        description: 'Erreur métier (géocodage / périmètre géographique)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 400),
                new OA\Property(property: 'detail', type: 'string', example: 'Le géocodage de la voie a échoué.'),
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
        #[MapRequestPayload] RegulationGeneralInfoDTO $dto,
    ): JsonResponse {
        /** @var OrganizationAwareUserInterface $user */
        $user = $this->security->getUser();
        $organization = $user->getOrganization();

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->source = RegulationOrderRecordSourceEnum::API->value;
        $generalInfo->organization = $organization;
        $this->objectMapper->map($dto, $generalInfo);

        $regulationOrderRecord = $this->commandBus->handle(new SaveRegulationWithMeasureCommand($generalInfo, $dto->status, $dto->measures));
        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        return new JsonResponse(
            ['uuid' => $regulationOrderRecord->getUuid()],
            Response::HTTP_CREATED,
        );
    }
}
