<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TokenRepository extends ServiceEntityRepository implements TokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function add(Token $token): Token
    {
        $this->getEntityManager()->persist($token);

        return $token;
    }

    public function remove(Token $token): void
    {
        $this->getEntityManager()->remove($token);
    }

    public function findOneByTokenAndType(string $token, string $type): ?Token
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.type = :type')
            ->setParameters([
                'token' => $token,
                'type' => $type,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
