<?php

declare(strict_types=1);

namespace App\Test\Integration\Infrastructure\Templates;

use App\Domain\Regulation\Repository\AdministratorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdministratorRepositoryTest extends KernelTestCase
{
    public function testRender(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var AdministratorRepositoryInterface */
        $repository = $container->get(AdministratorRepositoryInterface::class);

        $administrators = $repository->findAll();

        // Basic case
        $this->assertContains('Nord', $administrators);
        // Cases across file lines
        $this->assertContains('Calvados', $administrators);
        $this->assertContains('Deux-Sèvres', $administrators);
        $this->assertContains('Haute-Saône', $administrators, implode(', ', $administrators));
    }
}
