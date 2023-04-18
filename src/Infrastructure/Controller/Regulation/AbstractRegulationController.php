<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Security\SymfonyUser;
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

    protected function getRegulationOrderRecord(string $uuid): RegulationOrderRecord
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        $regulationOrderRecord = null;

        try {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            throw new NotFoundHttpException();
        }

        /** @var SymfonyUser */
        $user = $this->security->getUser();

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $user->getOrganization())) {
            throw new AccessDeniedHttpException();
        }

        return $regulationOrderRecord;
    }
}
