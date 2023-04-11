<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Controller\Regulation\Blocks\GeneralInfoFormControllerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateRegulationController
{
    use GeneralInfoFormControllerTrait;

    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private Security $security,
        private TranslatorInterface $translator,
        private QueryBusInterface $queryBus,
        private RouterInterface $router,
    ) {
    }

    protected function getTemplateName(): string
    {
        return 'regulation/create.html.twig';
    }

    protected function getSuccessUrl(RegulationOrderRecord $regulationOrderRecord): string
    {
        return $this->router->generate('app_regulation_detail', [
            'uuid' => $regulationOrderRecord->getUuid(),
        ]);
    }

    #[Route(
        '/regulations/form',
        name: 'app_regulation_create',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid = null): Response
    {
        $command = new SaveRegulationOrderCommand();

        return $this->handle($request, $command);
    }
}
