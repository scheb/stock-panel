<?php

declare(strict_types=1);

namespace App\Provider;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockEarningsProvider
{
    public function __construct(
        public readonly StockRepository $stockRepository,
        public readonly YahooFinanceApi $yahooFinanceApi,
        public readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return iterable<Stock>
     */
    public function getStocksWithEarnings(): iterable
    {
        return $this->stockRepository->getStocksWithEarnings();
    }

    public function updateStockEarningTimes(): void
    {
        $stocks = $this->stockRepository->getAll();
        foreach ($stocks as $stock) {
            foreach ($stock->getSymbols() as $symbol) {
                try {
                    $earnings = $this->yahooFinanceApi->getNextEarningsDate($symbol);
                    if (isset($earnings[0]['raw'])) {
                        $earningsTimestamp = $earnings[0]['raw'];
                        $stock->setNextEarningsTime(new \DateTime('@' . $earningsTimestamp));
                        $this->em->persist($stock);
                        break;
                    }
                } catch (\Exception) {
                }
            }
        }
        $this->em->flush();
    }
}
