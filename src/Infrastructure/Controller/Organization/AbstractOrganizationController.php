<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization;

use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractOrganizationController
{
    public function __construct(
        protected QueryBusInterface $queryBus,
        protected Security $security,
    ) {
    }

    protected function getOrganization(string $uuid): Organization
    {
        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($uuid));
        } catch (OrganizationNotFoundException) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(OrganizationVoter::VIEW, $organization)) {
            throw new AccessDeniedHttpException();
        }

        return $organization;
    }
}
