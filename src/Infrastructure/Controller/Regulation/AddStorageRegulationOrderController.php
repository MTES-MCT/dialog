<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Form\Regulation\StorageRegulationOrderFormType;
use App\Infrastructure\Security\Voter\RegulationOrderRecordVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Turbo\TurboBundle;

final class AddStorageRegulationOrderController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/storage/add',
        name: 'app_config_regulation_add_storage',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        if (!$this->security->isGranted(RegulationOrderRecordVoter::PUBLISH, $regulationOrderRecord)) {
            throw new AccessDeniedHttpException();
        }

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $command = new SaveRegulationOrderStorageCommand($regulationOrder, null);
        $form = $this->formFactory->create(
            StorageRegulationOrderFormType::class,
            $command,
            [
                'action' => $this->router->generate('app_config_regulation_add_storage', ['uuid' => $uuid]),
            ],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            $redirectUrl = $this->router->generate('app_regulation_detail', ['uuid' => $uuid]);

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                return new Response(
                    $this->twig->render(
                        'regulation/fragments/_storage.regulation.stream.html.twig',
                        [
                            'redirectUrl' => $redirectUrl,
                        ],
                    ),
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/vnd.turbo-stream.html'],
                );
            }

            return new RedirectResponse($redirectUrl, Response::HTTP_SEE_OTHER);
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            content: $this->twig->render(
                name: 'regulation/fragments/_storage.regulation.stream.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
