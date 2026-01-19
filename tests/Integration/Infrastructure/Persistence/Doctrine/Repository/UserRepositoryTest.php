<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\UserExportView;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UserRepositoryTest extends AbstractWebTestCase
{
    public function testFindAllForExport(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $result = $userRepository->findAllForExport();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, \count($result));

        foreach ($result as $item) {
            $this->assertInstanceOf(UserExportView::class, $item);
            $this->assertIsString($item->fullName);
            $this->assertIsString($item->email);
            $this->assertNotEmpty($item->fullName);
            $this->assertNotEmpty($item->email);
        }

        $emails = array_map(fn (UserExportView $view) => $view->email, $result);
        $this->assertContains(UserFixture::DEPARTMENT_93_ADMIN_EMAIL, $emails);
        $this->assertContains(UserFixture::DEPARTMENT_93_USER_EMAIL, $emails);
    }

    public function testFindAllForExportReturnsValidEmailFormat(): void
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $result = $userRepository->findAllForExport();

        foreach ($result as $item) {
            $this->assertStringContainsString('@', $item->email);
        }
    }
}
