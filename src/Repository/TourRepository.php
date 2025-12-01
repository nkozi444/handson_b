<?php

namespace App\Repository;

use App\Entity\Tour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tour::class);
    }

    /**
     * Search tours with joined exhibition by query string & optional date (YYYY-MM-DD)
     *
     * @return Tour[]
     */
    public function searchWithExhibition(?string $q): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.exhibition', 'e')
            ->addSelect('e')
            ->orderBy('t.id', 'ASC');

        $q = $q !== null ? trim($q) : '';

        if ($q !== '') {
            $qb->andWhere('
                LOWER(COALESCE(t.name, \'\'))           LIKE :q
                OR LOWER(COALESCE(t.email, \'\'))       LIKE :q
                OR LOWER(COALESCE(t.phoneNumber, \'\')) LIKE :q
                OR LOWER(COALESCE(t.status, \'\'))      LIKE :q
                OR LOWER(COALESCE(t.notes, \'\'))       LIKE :q
                OR LOWER(COALESCE(e.title, \'\'))       LIKE :q
                OR LOWER(COALESCE(e.type, \'\'))        LIKE :q
                OR LOWER(COALESCE(e.period, \'\'))      LIKE :q
            ')
            ->setParameter('q', '%' . mb_strtolower($q) . '%');

            $asDate = \DateTimeImmutable::createFromFormat('Y-m-d', $q);
            if ($asDate instanceof \DateTimeImmutable) {
                $qb->orWhere('t.date BETWEEN :start AND :end')
                   ->setParameter('start', $asDate->setTime(0, 0))
                   ->setParameter('end',   $asDate->setTime(23, 59, 59));
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function countUpcoming(): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.date IS NOT NULL')
            ->andWhere('t.date >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Tour[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.exhibition', 'e')
            ->addSelect('e')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Status counts for chart: returns ['confirmed' => 3, 'pending' => 5, ...]
     */
    public function getStatusCounts(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('LOWER(t.status) AS status', 'COUNT(t.id) AS cnt')
            ->groupBy('status')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $status = $row['status'] ?? 'unknown';
            $out[$status] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * Daily counts between two dates (inclusive)
     * Returns rows like ['date' => '2025-11-25', 'count' => 4]
     *
     * @return array<int,array{date:string,count:int}>
     */
    public function getDailyCounts(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select("FUNCTION('DATE', t.date) AS day", 'COUNT(t.id) AS cnt')
            ->andWhere('t.date IS NOT NULL')
            ->andWhere('t.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to->setTime(23, 59, 59))
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'date'  => $row['day'],
                'count' => (int) $row['cnt'],
            ];
        }

        return $out;
    }
}
