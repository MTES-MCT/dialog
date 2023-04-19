<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Common;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class PaginationTest extends WebTestCase
{
    public function testRender(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $twig = $container->get(\Twig\Environment::class);

        $html = $twig->render('common/pagination.html.twig', [
            'queryParams' => [
                'tab' => 'temporary',
                'page' => 1,
                'pageSize' => 10,
            ],
            'pagination' => [
                'windowPages' => [3, 4, 5],
                'lastPage' => 15,
                'hasFirstPageLandmark' => true,
                'hasLeftTruncature' => true,
                'hasRightTruncature' => true,
                'hasLastPageLandmark' => true,
                'items' => [],
                'totalItems' => 50,
            ],
            'currentPage' => 3,
        ]);

        $crawler = new Crawler($html);
        $navLi = $crawler->filter('nav.fr-pagination')->filter('li');

        $this->assertSame('Première page', $navLi->eq(0)->filter('a')->text());
        $this->assertSame('Page précédente', $navLi->eq(1)->filter('a')->text());
        $this->assertSame('1', $navLi->eq(2)->filter('a')->text());
        $this->assertSame('...', $navLi->eq(3)->filter('span')->text());
        $this->assertSame('3', $navLi->eq(4)->filter('a')->text());
        $this->assertSame('4', $navLi->eq(5)->filter('a')->text());
        $this->assertSame('5', $navLi->eq(6)->filter('a')->text());
        $this->assertSame('...', $navLi->eq(7)->filter('span')->text());
        $this->assertSame('15', $navLi->eq(8)->filter('a')->text());
        $this->assertSame('Page suivante', $navLi->eq(9)->filter('a')->text());
        $this->assertSame('Dernière page', $navLi->eq(10)->filter('a')->text());
    }
}
