<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getValue(string $key, ?string $default = null): ?string
    {
        /** @var Setting|null $s */
        $s = $this->findOneBy(['keyName' => $key, 'isActive' => true]);
        return $s?->getValue() ?? $default;
    }

    /** @return array<string, string|null> */
    public function getAllKeyValue(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->andWhere('s.isActive = true')
            ->orderBy('s.groupName', 'ASC')
            ->addOrderBy('s.keyName', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $s) {
            $out[$s->getKeyName()] = $s->getValue();
        }
        return $out;
    }
}
