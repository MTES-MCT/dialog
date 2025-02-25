<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\OrganizationRegulationAccessInterface;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

abstract class AbstractRegulationController
{
    public function __construct(
        protected QueryBusInterface $queryBus,
        protected Security $security,
        protected CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
    }

    protected function getRegulationOrderRecordUsing(callable $func, bool $requireUserSameOrg = true): mixed
    {
        /** @var ?OrganizationRegulationAccessInterface */
        $regulationOrderRecord = null;

        try {
            $regulationOrderRecord = \call_user_func($func);
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        if ($requireUserSameOrg) {
            /** @var AbstractAuthenticatedUser|null */
            $user = $this->security->getUser();

            if (!$user || !$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getUserOrganizationUuids())) {
                throw new AccessDeniedHttpException();
            }
        }

        return $regulationOrderRecord;
    }

    protected function getRegulationOrderRecord(string $uuid, bool $requireUserSameOrg = true): RegulationOrderRecord
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        return $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        }, $requireUserSameOrg);
    }
}
