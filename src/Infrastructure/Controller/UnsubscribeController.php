<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Application\User\Command\UnsubscribeUserCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnsubscribeController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private RouterInterface $router,
    ) {
    }

    #[Route('/unsubscribe/{email}', name: 'app_unsubscribe', methods: ['GET'])]
    public function __invoke(Request $request, string $email): Response
    {
        $this->commandBus->handle(new UnsubscribeUserCommand($email));

        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('unsubscribed.email'));

        return new RedirectResponse(
            url: $this->router->generate('app_landing'),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}
