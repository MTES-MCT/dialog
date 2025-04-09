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
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->leftJoin('ml.organization', 'o')
            ->getQuery()
            ->getResult();
    }
}
