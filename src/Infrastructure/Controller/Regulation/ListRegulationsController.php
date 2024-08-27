<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\User\Query\GetOrganizationsQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\Controller\DTO\ListRegulationsDTO;
use App\Infrastructure\Form\Regulation\RegulationListFiltersFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ListRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private TranslatorInterface $translator,
        private Security $security,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
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
        $dto = new ListRegulationsDTO();

        /** @var SymfonyUser|null */
        $user = $this->security->getUser();
        $organizations = $this->queryBus->handle(new GetOrganizationsQuery());

        if (!$user) {
            // Anonymous users can only see published regulation orders
            $dto->status = RegulationOrderRecordStatusEnum::PUBLISHED;

            // Prevent forcing through query parameter
            $request->query->set('status', $dto->status);
        }

        $form = $this->formFactory->createNamed(
            '', // Prettier URL (https://symfony.com/doc/current/forms.html#changing-the-form-name)
            type: RegulationListFiltersFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_regulations_list'),
                'method' => 'GET',
                // Prettier URL (CSRF not useful for GET requests because no data is modified)
                'csrf_protection' => false,
                'user' => $user,
                'organizations' => $organizations,
            ],
        );

        $form->handleRequest($request);

        $pageSize = min($request->query->getInt('pageSize', 20), 100);
        $page = $request->query->getInt('page', 1);

        if ($pageSize <= 0 || $page <= 0) {
            throw new BadRequestHttpException(
                $this->translator->trans('invalid.page_or_page_size', [], 'validators'),
            );
        }

        $organizationUserUuids = $dto->organization ? [$dto->organization] : null;

        $regulations = $this->queryBus->handle(
            new GetRegulationsQuery(
                pageSize: $pageSize,
                page: $page,
                identifier: $dto->identifier,
                organizationUuids: $organizationUserUuids,
                regulationOrderType: $dto->regulationOrderType,
                status: $dto->status,
            ),
        );

        return new Response($this->twig->render(
            name: 'regulation/index.html.twig',
            context: [
                'form' => $form->createView(),
                'regulations' => $regulations,
                'pageSize' => $pageSize,
                'page' => $page,
            ],
        ));
    }
}
