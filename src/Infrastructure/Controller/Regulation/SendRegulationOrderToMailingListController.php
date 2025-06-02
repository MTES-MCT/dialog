<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\Organization\MailingList\Command\SendRegulationOrderToMailingListCommand;
use App\Application\Organization\MailingList\Query\GetMailingListQuery;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Form\Organization\SendToMailingListFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendRegulationOrderToMailingListController extends AbstractRegulationController
{
    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private RouterInterface $router,
        private FormFactoryInterface $formFactory,
        private \Twig\Environment $twig,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        Security $security,
        QueryBusInterface $queryBus,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/mailing_list_share',
        name: 'app_mailing_list_share',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $user = $this->authenticatedUser->getUser();
        $recipients = $this->queryBus->handle(new GetMailingListQuery($regulationOrderRecord->getOrganizationUuid()));
        $command = new SendRegulationOrderToMailingListCommand($regulationOrder, $regulationOrderRecord, $user);
        $form = $this->formFactory->create(SendToMailingListFormType::class, $command, [
            'recipients' => $recipients, ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatchAsync($command);

            /** @var FlashBagAwareSessionInterface */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('mailing.list.share.succeeded'));

            return new RedirectResponse(
                url: $this->router->generate('app_mailing_list_share', [
                    'uuid' => $uuid,
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/send_to_mailing_list.html.twig',
                context: [
                    'form' => $form->createView(),
                    'uuid' => $uuid,
                    'identifier' => $regulationOrder->getIdentifier(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
