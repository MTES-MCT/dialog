<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller;

use App\Infrastructure\Controller\Regulation\ListRegulationsPresenter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @group only */
final class ListRegulationsPresenterTest extends TestCase
{
    public function testPresentPermanent()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $context = [
            'temporaryRegulations' => [],
            'permanentRegulations' => [],
            'tab' => $tab,
            'pageSize' => $pageSize,
            'page' => $page,
        ]

        $presenter = new ListRegulationsPresenter($translator);
        $this->assertSame([], $presenter->present($context));
    }
}
