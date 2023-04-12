<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Controller\Regulation\GeneralInfoFormTrait;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveGeneralInfoController extends AbstractRegulationController
{
    use GeneralInfoFormTrait;

    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private CommandBusInterface $commandBus,
        private Security $security,
        private TranslatorInterface $translator,
        QueryBusInterface $queryBus,
        private RouterInterface $router,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
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
        '/_fragment/regulations/{uuid}/general_info/form',
        name: 'fragment_regulations_general_info_form',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();

        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getOrganization())) {
            throw new AccessDeniedHttpException();
        }

        // TODO: rename to SaveRegulationGeneralInfoCommand
        $command = SaveRegulationOrderCommand::create($regulationOrderRecord);

        return $this->handleGeneralInfoForm($request, $user, $command);
    }
}
