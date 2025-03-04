<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Application\Organization\VisaModel\Query\GetVisaModelQuery;
use App\Application\QueryBusInterface;
use App\Domain\Organization\VisaModel\Exception\VisaModelNotFoundException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class VisaDetailController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{organizationUuid}/visa_models/{uuid}/detail',
        name: 'app_config_visa_models_detail',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $organizationUuid, string $uuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::VIEW, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $visaModel = $this->queryBus->handle(new GetVisaModelQuery($uuid));
        } catch (VisaModelNotFoundException) {
            throw new NotFoundHttpException();
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/visa_model/visa_detail.html.twig',
                context: [
                    'organization' => $organization,
                    'visaModel' => $visaModel,
                ],
            ),
        );
    }
}
