<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRestrictionsQuery;
use App\Domain\Pagination;
use App\Domain\Regulation\DTO\RestrictionListFilterDTO;
use App\Infrastructure\Form\Regulation\RestrictionListFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RestrictionListController
{
    public function __construct(
        private \Twig\Environment $twig,
        private TranslatorInterface $translator,
        private FormFactoryInterface $formFactory,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/restrictions',
        name: 'app_restrictions_list',
        requirements: ['page' => '\d+'],
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] int $page = Pagination::DEFAULT_PAGE,
        #[MapQueryParameter] int $pageSize = Pagination::DEFAULT_PAGE_SIZE_RESTRICTIONS,
    ): Response {
        $pageSize = min($pageSize, 100);

        if ($page <= 0 || $pageSize <= 0) {
            throw new BadRequestHttpException(
                $this->translator->trans('invalid.page_or_page_size', [], 'validators'),
            );
        }

        $dto = new RestrictionListFilterDTO($this->dateUtils->getNow());
        $dto->page = $page;
        $dto->pageSize = $pageSize;

        $form = $this->formFactory->create(
            type: RestrictionListFilterFormType::class,
            data: $dto,
            options: [
                // 'action' => $this->router->generate('app_restrictions_list_items'),
                'method' => 'GET',
            ],
        );

        $form->handleRequest($request);

        $query = new GetRestrictionsQuery($dto);
        $pagination = $this->queryBus->handle($query);

        // $pagination = new Pagination(
        //     items: [],
        //     totalItems: 21,
        //     currentPage: $page,
        //     pageSize: $pageSize,
        // );

        return new Response($this->twig->render(
            name: 'restriction/list.html.twig',
            context: [
                'form' => $form->createView(),
                'pageSize' => $dto->pageSize,
                'page' => $dto->page,
                'pagination' => $pagination,
            ],
        ));
    }
}
