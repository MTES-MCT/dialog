<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Blocks;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GeneralInfoFormController extends AbstractRegulationController
{
    use GeneralInfoFormControllerTrait;

    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private Security $security,
        private TranslatorInterface $translator,
        QueryBusInterface $queryBus,
        private RouterInterface $router,
    ) {
        parent::__construct($queryBus);
    }

    protected function getGeneralInfoFormTemplateName(): string
    {
        return 'regulation/fragments/_general_info_form.html.twig';
    }

    protected function getGeneralInfoFormSuccessUrl(RegulationOrderRecord $regulationOrderRecord): string
    {
        return $this->router->generate('fragment_regulations_general_info', [
            'uuid' => $regulationOrderRecord->getUuid(),
        ]);
    }

    #[Route(
        '/_fragment/regulations/{uuid}/general/form',
        name: 'fragment_regulations_general_info_form',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        // TODO: rename to SaveRegulationGeneralInfoCommand
        $command = SaveRegulationOrderCommand::create($regulationOrderRecord);

        return $this->handleGeneralInfoForm($request, $command);
    }
}
