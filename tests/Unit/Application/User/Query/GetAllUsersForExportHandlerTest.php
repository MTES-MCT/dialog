<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetAllUsersForExport;
use App\Application\User\Query\GetAllUsersForExportHandler;
use App\Domain\User\Exception\EmptyUsersRepositoryException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\UserExportView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final class GetAllUsersForExportHandlerTest extends TestCase
{
    public function testExportUsersSuccessfully(): void
    {
        $users = [
            new UserExportView('John Doe', 'john@example.com'),
            new UserExportView('Jane Smith', 'jane@example.com'),
        ];

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findAllForExport')
            ->willReturn($users);

        $expectedCsv = <<<CSV
        "Nom complet","Email"
        "John Doe","john@example.com"
        "Jane Smith","jane@example.com"
        CSV;

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with($users, CsvEncoder::FORMAT)
            ->willReturn($expectedCsv);

        $handler = new GetAllUsersForExportHandler($userRepository, $serializer);
        $result = $handler(new GetAllUsersForExport());

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

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('serialize');

        $handler = new GetAllUsersForExportHandler($userRepository, $serializer);
        $handler(new GetAllUsersForExport());
    }
}
