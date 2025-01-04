<?php

namespace App\Provider;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\Quote;

class StockPriceProvider
{
    private const FETCH_QUOTES_MAX_TRIES = 3;
    private const UPDATE_PERIOD_MINUTES = 5;
    private const string DEFAULT_CATEGORY = 'Sonstige';
    private const string FAVOURITES_CATEGORY = 'Favoriten';

    private StockRepository $stockRepo;

    public function __construct(
        private EntityManagerInterface $em,
        private ApiClient $api
    ) {
        $this->stockRepo = $em->getRepository(Stock::class);
    }

    /**
     * @return Stock[]
     */
    public function getStocks(): array
    {
        $stocks = $this->stockRepo->getAll();
        return $stocks;
    }

    /**
     * @return Stock[][]
     */
    public function getCategorizedStocks(): array
    {
        $categories = [];
        $favourites = [];
        $uncategorized = [];
        $stocks = $this->getStocks();
        foreach ($stocks as $stock) {
            if ($stock->isFavourite()) {
                $favourites[] = $stock;
                continue;
            }

            $category = $stock->getCategory();
            if (!$category) {
                $uncategorized[] = $stock;
                continue;
            }

            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $stock;
        }
        $all = [];
        if ($favourites) {
            $all[self::FAVOURITES_CATEGORY] = $favourites;
        }
        $all = array_merge($all, $categories);
        if ($uncategorized) {
            $all[self::DEFAULT_CATEGORY] = $uncategorized;
        }

        return $all;
    }

    public function initStock(Stock $stock): Stock
    {
        $data = $this->fetchData([$stock->getSymbol()]);
        if (count($data) == 1) {
            $quote = $data[0];
            [$priceMarket, $price, $priceChange, $priceTime] = $this->getMostRecentPrice($quote);
            $stock
                ->setCurrentPrice($price)
                ->setCurrentPriceTime($priceTime)
                ->setCurrentPriceMarket($priceMarket)
                ->setCurrentChange($priceChange)
                ->setUpdatedAt(new \DateTime());
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
            [$priceMarket, $price, $priceChange, $priceTime] = $this->getMostRecentPrice($quote);
            $stock
                ->setCurrentPrice($price)
                ->setCurrentPriceTime($priceTime)
                ->setCurrentPriceMarket($priceMarket)
                ->setCurrentChange($priceChange)
                ->setUpdatedAt(new \DateTime());
            $this->em->persist($stock);
        }
        $this->em->flush();
    }

    private function getMostRecentPrice(Quote $quote): array
    {
        if ($quote->getPreMarketPrice() && $quote->getPreMarketTime() > $quote->getRegularMarketTime()) {
            return [Stock::PRICE_TYPE_PRE_MARKET, $quote->getPreMarketPrice(), $quote->getPreMarketChange(), $quote->getPreMarketTime()];
        }

        if ($quote->getPostMarketPrice() && $quote->getPostMarketTime() > $quote->getRegularMarketTime()) {
            return [Stock::PRICE_TYPE_POST_MARKET, $quote->getPostMarketPrice(), $quote->getPostMarketChange(), $quote->getPostMarketTime()];
        }

        return [Stock::PRICE_TYPE_REGULAR_MARKET, $quote->getRegularMarketPrice(), $quote->getRegularMarketChange(), $quote->getRegularMarketTime()];
    }

    public function hasToUpdate(): bool
    {
        $timeout = new \DateTime("-" . self::UPDATE_PERIOD_MINUTES . " minutes");
        return $this->stockRepo->getLastUpdate() < $timeout;
    }

    /**
     * @return Quote[]
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
