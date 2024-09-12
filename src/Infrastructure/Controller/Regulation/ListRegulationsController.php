<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Application\User\Query\GetOrganizationsQuery;
use App\Domain\Regulation\DTO\RegulationListFiltersDTO;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
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
        $dto = new RegulationListFiltersDTO();

        $dto->pageSize = min($request->query->getInt('pageSize', RegulationListFiltersDTO::DEFAULT_PAGE_SIZE), 100);
        $dto->page = $request->query->getInt('page', RegulationListFiltersDTO::DEFAULT_PAGE);

        if ($dto->pageSize <= 0 || $dto->page <= 0) {
            throw new BadRequestHttpException(
                $this->translator->trans('invalid.page_or_page_size', [], 'validators'),
            );
        }

        /** @var SymfonyUser|null */
        $user = $this->security->getUser();
        $organizations = $this->queryBus->handle(new GetOrganizationsQuery());

        if (!$user) {
            // Anonymous users can only see published regulation orders
            $dto->status = RegulationOrderRecordStatusEnum::PUBLISHED->value;

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

        $regulations = $this->queryBus->handle(new GetRegulationsQuery($dto));

        return new Response($this->twig->render(
            name: 'regulation/index.html.twig',
            context: [
                'form' => $form->createView(),
                'regulations' => $regulations,
                'pageSize' => $dto->pageSize,
                'page' => $dto->page,
            ],
        ));
    }
}
