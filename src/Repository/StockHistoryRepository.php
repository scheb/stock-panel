<?php

namespace App\Repository;

use App\Entity\Stock;
use App\Entity\StockHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockHistory::class);
    }

    public function getChangeHistory(Stock $stock): array
    {
        $dailyChange = [];
        $previousDayPrice = null;
        foreach ($this->getHistory($stock) as $day) {
            $thisDayPrice = $day['maxPrice'];
            if ($previousDayPrice !== null) {
                $change = ($thisDayPrice - $previousDayPrice) / $previousDayPrice;
                $date = $day['date']->format('Y-m-d');
                $dailyChange[$date] = $change;
            }

            $previousDayPrice = $thisDayPrice;
        }

        return $dailyChange;
    }

    private function getHistory(Stock $stock)
    {
        $minDate = new \DateTime('-28 days');
        return $this->createQueryBuilder('sh')
            ->select('sh.date', 'MAX(sh.price) as maxPrice')
            ->where('sh.stock = :stock')
            ->setParameter('stock', $stock)
            ->andWhere('sh.date >= :minDate')
            ->setParameter('minDate', $minDate)
            ->groupBy('sh.date')
            ->orderBy('sh.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function insertOrUpdate(StockHistory $stockHistory): void
    {
        $em = $this->getEntityManager();
        $existingEntity = $this->findOneBy([
            'stock' => $stockHistory->getStock(),
            'symbol' => $stockHistory->getSymbol(),
            'date' => $stockHistory->getDate()
        ]);

        if ($existingEntity) {
            $existingEntity->setPrice($stockHistory->getPrice());
            $em->persist($existingEntity);
        } else {
            $em->persist($stockHistory);
        }

        $em->flush();
    }
}
