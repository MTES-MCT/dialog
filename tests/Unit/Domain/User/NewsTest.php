<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\News;
use PHPUnit\Framework\TestCase;

final class NewsTest extends TestCase
{
    public function testGetters(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');
        $news = new News('9cebe00d-04d8-48da-89b1-059f6b7bfe44');
        $news->setCreatedAt($date);
        $news->setName('Dernières nouveautés');
        $news->setLinkTitle('Découvrez les dernières nouveautés sur DiaLog');
        $news->setLink('https://www.dialog.beta.gouv.fr/regulations/export');
        $news->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $news->getUuid());
        $this->assertSame('Dernières nouveautés', $news->getName());
        $this->assertSame('Découvrez les dernières nouveautés sur DiaLog', $news->getLinkTitle());
        $this->assertSame('https://www.dialog.beta.gouv.fr/regulations/export', $news->getLink());
        $this->assertSame('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', $news->getContent());
        $this->assertSame($date, $news->getCreatedAt());
    }
}
