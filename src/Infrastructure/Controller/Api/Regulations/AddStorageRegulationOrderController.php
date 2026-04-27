<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\GetStorageRegulationOrderQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\OrganizationAwareUserInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddStorageRegulationOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route(
        '/api/regulations/{identifier}/storage',
        name: 'api_regulations_add_storage',
        methods: ['POST'],
        requirements: ['identifier' => '.+'],
    )]
    #[OA\Tag(name: 'Private')]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                description: 'Fournir soit `file`, soit `url` (mutuellement exclusifs). `title` est obligatoire avec `url`.',
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary', nullable: true, description: 'Document de l\'arrêté officiel (pdf, docx, odt, jpg). Taille max : 5 Mo.'),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/arrete.pdf'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 30, nullable: true, example: 'Arrêté municipal'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Document attaché à l\'arrêté',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', example: 'F2025/001'),
                new OA\Property(property: 'url', type: 'string', nullable: true, example: 'https://example.com/arrete.pdf'),
                new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Arrêté municipal'),
                new OA\Property(property: 'mimeType', type: 'string', nullable: true, example: 'PDF'),
                new OA\Property(property: 'fileSize', type: 'integer', nullable: true, description: 'Taille en kilo-octets', example: 248),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Document mis à jour',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'identifier', type: 'string', example: 'F2025/001'),
                new OA\Property(property: 'url', type: 'string', nullable: true),
                new OA\Property(property: 'title', type: 'string', nullable: true),
                new OA\Property(property: 'mimeType', type: 'string', nullable: true),
                new OA\Property(property: 'fileSize', type: 'integer', nullable: true),
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
                            new OA\Property(property: 'propertyPath', type: 'string', example: 'file'),
                            new OA\Property(property: 'title', type: 'string', example: 'Le fichier est trop volumineux.'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(Request $request, string $identifier): JsonResponse
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

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $existingStorage = $this->queryBus->handle(new GetStorageRegulationOrderQuery($regulationOrder));

        $file = $request->files->get('file');
        $url = $request->request->get('url');
        $title = $request->request->get('title');

        if ($file !== null && !empty($url)) {
            return $this->violation('', 'Veuillez fournir soit un fichier, soit une URL, mais pas les deux.');
        }

        if ($file === null && empty($url)) {
            return $this->violation('', 'Veuillez fournir soit un fichier, soit une URL.');
        }

        $command = new SaveRegulationOrderStorageCommand($regulationOrder, $existingStorage);

        if ($file !== null) {
            $command->file = $file;
            $command->url = null;
            $command->title = $title;
        } else {
            $command->url = $url;
            $command->title = $title;
        }

        $errors = $this->validator->validate($command);

        if (\count($errors) > 0) {
            $violations = [];
            foreach ($errors as $error) {
                $violations[] = [
                    'propertyPath' => $error->getPropertyPath(),
                    'title' => $error->getMessage(),
                ];
            }

            return new JsonResponse([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => 'Validation failed',
                'violations' => $violations,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $isCreation = $existingStorage === null;
        $this->commandBus->handle($command);
        $this->commandBus->handle(new UpdateApiClientLastUsedAtCommand($user->getUserIdentifier()));

        $storage = $this->queryBus->handle(new GetStorageRegulationOrderQuery($regulationOrder));

        return new JsonResponse(
            [
                'identifier' => $regulationOrder->getIdentifier(),
                'url' => $storage?->getUrl(),
                'title' => $storage?->getTitle(),
                'mimeType' => $storage?->getMimeType(),
                'fileSize' => $storage?->getFileSize(),
            ],
            $isCreation ? Response::HTTP_CREATED : Response::HTTP_OK,
        );
    }

    private function violation(string $propertyPath, string $message): JsonResponse
    {
        return new JsonResponse([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'Validation failed',
            'violations' => [[
                'propertyPath' => $propertyPath,
                'title' => $message,
            ]],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
