<?php

namespace App\Provider;

use App\Entity\Stock;
use Doctrine\Common\Persistence\ObjectManager;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\Quote;

class StockPriceProvider
{
    const FETCH_QUOTES_MAX_TRIES = 3;
    const UPDATE_PERIOD_MINUTES = 5;
    /**
     * @var \Scheb\YahooFinanceApi\ApiClient
     */
    private $api;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $em;

    /**
     * @var \App\Repository\StockRepository
     */
    private $stockRepo;

    public function __construct(ObjectManager $em, ApiClient $api)
    {
        $this->em = $em;
        $this->stockRepo = $em->getRepository(Stock::class);
        $this->api = $api;
    }

    /**
     * @return Stock[]
     */
    public function getStocksAndUpdate(): array
    {
        if ($this->hasToUpdate()) {
            $this->updateStocks();
        }

        return $this->getStocks();
    }

    /**
     * @return Stock[]
     */
    public function getStocks(): array
    {
        $stocks = $this->stockRepo->getAll();
        return $stocks;
    }

    public function createStock(string $symbol): Stock
    {
        $stock = new Stock();
        $data = $this->fetchData([$symbol]);
        if (count($data) == 1) {
            $quote = $data[0];
            $stock
                ->setName($quote->getLongName())
                ->setSymbol($quote->getSymbol())
                ->setInitialPrice($this->getCurrentPrice($quote));
        }
        return $stock;
    }

    public function updateStocks(): void
    {
        $stocks = $this->getStocks();
        $symbols = array_keys($stocks);
        $data = $this->fetchData($symbols);
        foreach ($data as $quote) {
            $symbol = $quote->getSymbol();
            $stock = $stocks[$symbol];
            $stock
                ->setCurrentPrice($this->getCurrentPrice($quote))
                ->setCurrentChange($this->getCurrentChange($quote))
                ->setUpdatedAt(new \DateTime());
            $this->em->persist($stock);
        }
        $this->em->flush();
    }

    private function getCurrentPrice(Quote $quote): ?float
    {
        // Take the most recent one
        return $quote->getPostMarketTime() > $quote->getRegularMarketTime()
            ? $quote->getPostMarketPrice()
            : $quote->getRegularMarketPrice();
    }

    private function getCurrentChange(Quote $quote): ?float
    {
        // Take the most recent one
        return $quote->getPostMarketTime() > $quote->getRegularMarketTime()
            ? $quote->getPostMarketChange()
            : $quote->getRegularMarketChange();
    }

    public function hasToUpdate(): bool
    {
        $timeout = new \DateTime("-" . self::UPDATE_PERIOD_MINUTES . " minutes");
        return $this->stockRepo->getLastUpdate() < $timeout;
    }

    /**
     * @return array|Quote[]
     */
    private function fetchData(array $symbols, int $try = 0): ?array
    {
        if (!$symbols) {
            return [];
        }
        try {
            return $this->api->getQuotes($symbols);
        } catch (ApiException $e) {
            // Retry if the query files
            if ($try < self::FETCH_QUOTES_MAX_TRIES) {
                return $this->fetchData($symbols, $try + 1);
            }
        }
        return [];
    }
}
