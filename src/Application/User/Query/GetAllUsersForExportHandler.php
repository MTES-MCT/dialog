<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\CsvExporterInterface;
use App\Domain\User\Exception\EmptyUsersRepositoryException;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class GetAllUsersForExportHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CsvExporterInterface $csvExporter,
    ) {
    }

    public function __invoke(GetAllUsersForExport $query): string
    {
        $users = $this->userRepository->findAllForExport();

        if (empty($users)) {
            throw new EmptyUsersRepositoryException();
        }

        return $this->csvExporter->export($users);
    }
}
