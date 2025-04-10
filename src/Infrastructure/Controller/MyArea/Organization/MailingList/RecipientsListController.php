<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Application\Organization\MailingList\Query\GetMailingListQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RecipientsListController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        path: '/organizations/{uuid}/recipients',
        name: 'app_config_recipients_list',
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $mailingLists = $this->queryBus->handle(new GetMailingListQuery($uuid));

        return new Response($this->twig->render(
            name: 'my_area/organization/mailing_list/index.html.twig',
            context: [
                'organization' => $organization,
                'mailingLists' => $mailingLists,
            ],
        ));
    }
}
