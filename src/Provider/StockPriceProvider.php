<?php

namespace App\Provider;

use App\Entity\Exchange;
use App\Entity\RecentPrice;
use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\HistoricalData;
use Scheb\YahooFinanceApi\Results\Quote;

class StockPriceProvider
{
    private const int FETCH_QUOTES_MAX_TRIES = 3;
    private const int UPDATE_PERIOD_MINUTES = 5;

    private const string ETFS_CATEGORY = 'ETFs';
    private const string DEFAULT_CATEGORY = 'Sonstige';
    private const string FAVOURITES_CATEGORY = '⭐ Favoriten';
    private const string WATCHLIST_CATEGORY = '👀 Watchlist';

    private array $exchangeRates = [];

    private StockRepository $stockRepo;

    public function __construct(
        private EntityManagerInterface $em,
        private ApiClient $api,
        private EventDispatcherInterface $eventDispatcher,
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
        $watchlist = [];
        $uncategorized = [];
        $etfs = [];
        $stocks = $this->getStocks();
        foreach ($stocks as $stock) {
            if ($stock->isFavourite()) {
                $favourites[] = $stock;
                continue;
            }

            if (!$stock->getQuantity()) {
                $watchlist[] = $stock;
                continue;
            }

            $category = $stock->getCategory();
            if (!$category) {
                $uncategorized[] = $stock;
                continue;
            }

            if (self::ETFS_CATEGORY === $category) {
                $etfs[] = $stock;
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
        ksort($categories);
        $all = array_merge($all, $categories);
        if ($uncategorized) {
            $all[self::DEFAULT_CATEGORY] = $uncategorized;
        }
        if ($etfs) {
            $all[self::ETFS_CATEGORY] = $etfs;
        }
        if ($watchlist) {
            $all[self::WATCHLIST_CATEGORY] = $watchlist;
        }

        return $all;
    }

    public function initStock(Stock $stock): Stock
    {
        $symbols = $stock->getSymbols();
        $quotes = $this->fetchQuotes($symbols);
        $mostRecentPrice = $this->getMostRecentPrice($symbols, $quotes);
        if ($mostRecentPrice) {
            $this->updateStockPrice($stock, $mostRecentPrice);
        }

        return $stock;
    }

    public function updateStocks(): void
    {
        $stocks = $this->getStocks();
        $symbols = array_merge(...array_map(function (Stock $stock) { return $stock->getSymbols(); }, $stocks));
        $quotes = $this->fetchQuotes($symbols);
        foreach ($stocks as $stock) {
            $mostRecentPrice = $this->getMostRecentPrice($stock->getSymbols(), $quotes);
            if (!$mostRecentPrice) {
                continue;
            }

            // Remember previous day's price
            if (
                null === $stock->getLastDayPriceTime()
                || $stock->getLastDayPriceTime()->format('Y-m-d') !== $mostRecentPrice->time->format('Y-m-d')
            ) {
                $stock->setLastDayPrice($stock->getCurrentPrice());
                $stock->setLastDayPriceTime($stock->getCurrentPriceTime());
            }

            // Update current price
            $this->updateStockPrice($stock, $mostRecentPrice);
            $this->em->persist($stock);
        }
        $this->em->flush();

        $this->eventDispatcher->dispatch(new StockUpdateEvent());
    }

    private function getMostRecentPrice(array $symbols, array $quotes): ?RecentPrice
    {
        $mostRecentPriceTime = null;
        $mostRecentPrice = null;
        foreach ($symbols as $symbol) {
            $quote = $quotes[$symbol];
            if (isset($quote)) {
                $recentPrice = RecentPrice::fromQuote($quote);
                if (!$mostRecentPriceTime || $recentPrice->time > $mostRecentPriceTime) {
                    $mostRecentPrice = $recentPrice;
                    $mostRecentPriceTime = $recentPrice->time;
                }
            }
        }

        return $mostRecentPrice;
    }

    public function hasToUpdate(): bool
    {
        $timeout = new \DateTime("-" . self::UPDATE_PERIOD_MINUTES . " minutes");
        return $this->stockRepo->getLastUpdate() < $timeout;
    }

    /**
     * @return Quote[] Indexed by symbol
     */
    private function fetchQuotes(array $symbols, int $try = 0): ?array
    {
        if (!$symbols) {
            return [];
        }

        try {
            $quotesIndex = [];
            $quotes = $this->api->getQuotes($symbols);
            foreach ($quotes as $quote) {
                $quotesIndex[$quote->getSymbol()] = $quote;
            }
            return $quotesIndex;
        } catch (ApiException $e) {
            // Retry if the query fails
            if ($try < self::FETCH_QUOTES_MAX_TRIES) {
                return $this->fetchQuotes($symbols, $try + 1);
            }
        }

        return [];
    }

    private function updateStockPrice(Stock $stock, RecentPrice $mostRecentPrice): void
    {
        try {
            $exchange = Exchange::from($mostRecentPrice->exchange);
        } catch (\ValueError) {
            $exchange = null;
        }

        // Currency conversion
        $stockCurrency = $stock->getCurrency();
        if ($stockCurrency !== $mostRecentPrice->currency) {
            try {
                $mostRecentPrice->price = $this->convertPrice($mostRecentPrice->price, $mostRecentPrice->currency, $stockCurrency);
                $mostRecentPrice->change = $this->convertPrice($mostRecentPrice->change, $mostRecentPrice->currency, $stockCurrency);
            } catch (\UnexpectedValueException $e) {
                return; // Cloud not determine exchange rate
            }
        }

        $stock
            ->setCurrentPrice($mostRecentPrice->price)
            ->setCurrentPriceTime($mostRecentPrice->time)
            ->setCurrentPriceMarket($mostRecentPrice->market)
            ->setCurrentPriceExchange($exchange)
            ->setCurrentPriceSymbol($mostRecentPrice->symbol)
            ->setCurrentChange($mostRecentPrice->change)
            ->setUpdatedAt(new \DateTime());
    }

    private function convertPrice(float $price, string $fromCurrency, string $toCurrency): ?float
    {
        return $price * $this->getExchangeRate($fromCurrency, $toCurrency);
    }

    private function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if (!isset($this->exchangeRates[$fromCurrency.':'.$toCurrency])) {
            $quote = $this->api->getExchangeRate($fromCurrency, $toCurrency);
            if (null === $quote) {
                throw new \UnexpectedValueException("Could not find exchange rate for $fromCurrency $toCurrency");
            }

            $this->exchangeRates[$fromCurrency.':'.$toCurrency] = (RecentPrice::fromQuote($quote))->price;
        }

        return $this->exchangeRates[$fromCurrency.':'.$toCurrency];
    }

    public function setLastDayPrices(): void
    {
        $stocks = $this->stockRepo->findAll();
        foreach ($stocks as $stock) {
            $symbols = $stock->getSymbols();
            $startDate = new \DateTime('-7 days');
            $endDate = new \DateTime('today');
            foreach ($symbols as $symbol) {
                $historicalData = $this->api->getHistoricalQuoteData($symbol, ApiClient::INTERVAL_1_DAY, $startDate, $endDate);
                if ($historicalData) {
                    /** @var HistoricalData $lastDayValues */
                    $lastDayValues = array_pop($historicalData);
                    $quote = $this->api->getQuote($symbol);

                    $lastDayPrice = $lastDayValues->getAdjClose() ?? $lastDayValues->getClose();
                    $lastDayPriceTime = $lastDayValues->getDate();

                    // Currency conversion
                    $stockCurrency = $stock->getCurrency();
                    if ($stockCurrency !== $quote->getCurrency()) {
                        try {
                            $lastDayPrice = $this->convertPrice($lastDayPrice, $quote->getCurrency(), $stockCurrency);
                        } catch (\UnexpectedValueException $e) {
                            return; // Cloud not determine exchange rate
                        }
                    }

                    $stock->setLastDayPrice($lastDayPrice);
                    $stock->setLastDayPriceTime($lastDayPriceTime);
                    $this->em->persist($stock);
                    break;
                }
            }
        }

        $this->em->flush();
    }
}
