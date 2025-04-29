<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\MailingList;

use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MailingListRepository extends ServiceEntityRepository implements MailingListRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, MailingList::class);
    }

    public function findRecipientsByOrganizationUuid(string $uuid): array
    {
        return $this->createQueryBuilder('ml')
            ->select('ml.uuid, ml.name, ml.email, ml.role')
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('ml.organization', 'o')
            ->getQuery()
            ->getResult();
    }

    public function add(MailingList $mailingList): MailingList
    {
        $this->getEntityManager()->persist($mailingList);

        return $mailingList;
    }

    public function findOneByUuid(string $uuid): MailingList
    {
        return $this->createQueryBuilder('ml')
            ->where('ml.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function remove(MailingList $mailingList): void
    {
        $this->getEntityManager()->remove($mailingList);
    }
}
