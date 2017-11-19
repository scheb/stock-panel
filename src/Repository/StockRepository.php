<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\ORM\EntityRepository;

class StockRepository extends EntityRepository
{
    public function getAll()
    {
        $qb = $this->createQueryBuilder("s");
        $qb->orderBy("s.name", "ASC");
        $stocks = $qb->getQuery()->execute();
        $index = [];
        /** @var Stock $stock */
        foreach ($stocks as $stock) {
            $index[$stock->getSymbol()] = $stock;
        }
        return $index;
    }

    public function getLastUpdate(): \DateTime
    {
        $qb = $this->createQueryBuilder("s");
        $qb->select("MAX(s.updatedAt)");
        $lastUpdate = $qb->getQuery()->getSingleScalarResult();
        return $lastUpdate ? new \DateTime($lastUpdate) : new \DateTime("-1 day");
    }
}
