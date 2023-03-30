<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Form\Regulation\PublishFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegulationDetailController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_detail',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        try {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        /** @var SymfonyUser */
        $user = $this->security->getUser();

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getOrganization())) {
            throw new AccessDeniedHttpException();
        }

        $command = new PublishRegulationCommand($regulationOrderRecord->uuid, $regulationOrderRecord->status);
        $form = $this->formFactory->create(PublishFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('app_regulations_list', [
                        'tab' => 'temporary',
                    ]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (RegulationOrderRecordCannotBePublishedException) {
                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $errorMsg = $this->translator->trans('regulation.detail.regulation_cant_be_published', [], 'validators');
                $session->getFlashBag()->add('error', $errorMsg);
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/detail.html.twig',
                context: [
                    'regulationOrderRecord' => $regulationOrderRecord,
                    'form' => $form->createView(),
                    'uuid' => $uuid,
                ],
            ),
        );
    }
}
