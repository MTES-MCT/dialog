<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Exception\EmptyUsersRepositoryException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class GetAllUsersForExportHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(GetAllUsersForExport $query): string
    {
        $users = $this->userRepository->findAllForExport();

        if (empty($users)) {
            throw new EmptyUsersRepositoryException();
        }

        return $this->serializer->serialize($users, CsvEncoder::FORMAT);
    }
}
