<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * @return iterable<Stock>
     */
    public function getAll(): iterable
    {
        $qb = $this->createQueryBuilder("s");
        $qb->orderBy("s.favourite", "DESC")->addOrderBy("s.name", "ASC");
        return $qb->getQuery()->execute();
    }

    public function getLastUpdate(): \DateTime
    {
        $qb = $this->createQueryBuilder("s");
        $qb->select("MAX(s.updatedAt)");
        $lastUpdate = $qb->getQuery()->getSingleScalarResult();
        return $lastUpdate ? new \DateTime($lastUpdate) : new \DateTime("-1 day");
    }

    public function getAllCategories(): array
    {
        $categories = $this->createQueryBuilder("s")
            ->select("DISTINCT s.category")
            ->orderBy("s.category", "ASC")
            ->where("s.category IS NOT NULL")
            ->getQuery()
            ->getResult();

        return array_map(fn (array $category) => $category['category'], $categories);
    }

    public function getStocksWithEarnings()
    {
        return $this->createQueryBuilder("s")
            ->orderBy("s.nextEarningsTime", "ASC")
            ->where("s.nextEarningsTime > :today")
            ->setParameter('today', new \DateTime("today"))
            ->getQuery()
            ->getResult();
    }
}
