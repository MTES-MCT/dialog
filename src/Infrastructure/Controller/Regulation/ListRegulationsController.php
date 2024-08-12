<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ListRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private TranslatorInterface $translator,
        private Security $security,
    ) {
    }

    #[Route(
        '/regulations',
        name: 'app_regulations_list',
        requirements: ['page' => '\d+'],
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var SymfonyUser|null */
        $user = $this->security->getUser();
        $pageSize = min($request->query->getInt('pageSize', 20), 100);
        $page = $request->query->getInt('page', 1);

        if ($pageSize <= 0 || $page <= 0) {
            throw new BadRequestHttpException(
                $this->translator->trans('invalid.page_or_page_size', [], 'validators'),
            );
        }

        $organizationUserUuids = $user?->getOrganizationUuids();

        $regulations = $this->queryBus->handle(
            new GetRegulationsQuery(
                pageSize: $pageSize,
                page: $page,
                organizationUuids: $organizationUserUuids,
            ),
        );

        return new Response($this->twig->render(
            name: 'regulation/index.html.twig',
            context: [
                'regulations' => $regulations,
                'pageSize' => $pageSize,
                'page' => $page,
            ],
        ));
    }
}
