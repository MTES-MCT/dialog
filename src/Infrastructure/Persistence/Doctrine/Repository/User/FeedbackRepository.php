<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\Feedback;
use App\Domain\User\Repository\FeedbackRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class FeedbackRepository extends ServiceEntityRepository implements FeedbackRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function add(Feedback $feedback): Feedback
    {
        $this->getEntityManager()->persist($feedback);

        return $feedback;
    }
}
