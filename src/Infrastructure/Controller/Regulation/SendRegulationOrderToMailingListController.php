<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\Organization\MailingList\Command\SendRegulationOrderToMailingListCommand;
use App\Application\Organization\MailingList\Query\GetMailingListQuery;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Infrastructure\Form\Organization\SendToMailingListFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
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
        private FormFactoryInterface $formFactory,
        private \Twig\Environment $twig,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private RouterInterface $router,
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
        $recipients = $this->queryBus->handle(new GetMailingListQuery($regulationOrderRecord->getOrganizationUuid()));

        $form = $this->formFactory->create(SendToMailingListFormType::class, null, ['recipients' => $recipients]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatchAsync(new SendRegulationOrderToMailingListCommand($recipients));

                /** @var FlashBagAwareSessionInterface */
                $session = $request->getSession();
                $session->getFlashBag()->add('success', $this->translator->trans('register.succeeded'));

                return new RedirectResponse(
                    url: $this->router->generate('app_regulation_detail'),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (OrganizationNotFoundException) {
                $form->get('organizationSiret')->addError(
                    new FormError($this->translator->trans('register.error.organizationSiret_not_found')),
                );
            } catch (UserAlreadyRegisteredException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('register.error.already_exists')),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/send_to_mailing_list.html.twig',
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
