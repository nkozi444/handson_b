<?php
// src/Repository/ExhibitionRepository.php
namespace App\Repository;

use App\Entity\Exhibition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExhibitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exhibition::class);
    }

    /**
     * Return currently active exhibitions.
     * If start/end dates are set, we check them; otherwise we use isActive flag.
     *
     * @return Exhibition[]
     */
    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'DESC');

        // prefer ones whose dates cover now, but simple approach:
        return $qb->getQuery()->getResult();
    }

    /**
     * Search by title, artists, period or type
     *
     * @return Exhibition[]
     */
    public function search(string $term, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('LOWER(e.title)', ':term'),
            $qb->expr()->like('LOWER(e.artists)', ':term'),
            $qb->expr()->like('LOWER(e.type)', ':term'),
            $qb->expr()->like('LOWER(e.period)', ':term')
        ))
        ->setParameter('term', '%'.mb_strtolower($term).'%')
        ->setMaxResults($limit)
        ->orderBy('e.startDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming exhibitions (start date in future)
     * @return Exhibition[]
     */
    public function findUpcoming(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    // add more helpers as you need...
}
