<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function searchLogs(?string $action, ?string $user, ?string $date): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($action) {
            $qb->andWhere('LOWER(l.action) = :action')
               ->setParameter('action', strtolower($action));
        }

        if ($user) {
            $qb->andWhere('LOWER(l.username) LIKE :user')
               ->setParameter('user', '%'.strtolower($user).'%');
        }

        if ($date) {
            $start = new \DateTimeImmutable($date . ' 00:00:00');
            $end   = $start->modify('+1 day');

            $qb->andWhere('l.createdAt >= :start AND l.createdAt < :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }
}
