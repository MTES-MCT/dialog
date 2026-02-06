<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\CsvExporterInterface;
use App\Application\User\Query\GetAllUsersForExportQuery;
use App\Application\User\Query\GetAllUsersForExportQueryHandler;
use App\Domain\User\Exception\EmptyUsersRepositoryException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\UserExportView;
use PHPUnit\Framework\TestCase;

final class GetAllUsersForExportQueryHandlerTest extends TestCase
{
    public function testExportUsersSuccessfully(): void
    {
        $registrationDate = new \DateTimeImmutable('2026-01-14');
        $lastActiveAt = new \DateTimeImmutable('2026-01-15');

        $users = [
            new UserExportView('John Doe', 'john@example.com', $registrationDate, $lastActiveAt, 'Orry la Ville'),
            new UserExportView('Jane Smith', 'jane@example.com', $registrationDate, null, 'Mairie de Paris'),
        ];

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findAllForExport')
            ->willReturn($users);

        $expectedCsv = <<<CSV
        "fullName","email",
        "John Doe","john@example.com"
        "Jane Smith","jane@example.com"
        CSV;

        $csvExporter = $this->createMock(CsvExporterInterface::class);
        $csvExporter
            ->expects(self::once())
            ->method('export')
            ->with($users)
            ->willReturn($expectedCsv);

        $handler = new GetAllUsersForExportQueryHandler($userRepository, $csvExporter);
        $result = $handler(new GetAllUsersForExportQuery());

        $this->assertSame($expectedCsv, $result);
    }

    public function testThrowsExceptionWhenNoUsers(): void
    {
        $this->expectException(EmptyUsersRepositoryException::class);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findAllForExport')
            ->willReturn([]);

        $csvExporter = $this->createMock(CsvExporterInterface::class);
        $csvExporter->expects(self::never())->method('export');

        $handler = new GetAllUsersForExportQueryHandler($userRepository, $csvExporter);
        $handler(new GetAllUsersForExportQuery());
    }
}
