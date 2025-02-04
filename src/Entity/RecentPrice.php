<?php

declare(strict_types=1);

namespace App\Entity;

use Scheb\YahooFinanceApi\Results\Quote;

class RecentPrice
{
    public string $market;
    public string $exchange;
    public string $symbol;
    public string $currency;
    public float $price;
    public float $change;
    public \DateTimeInterface $time;

    public static function fromQuote(Quote $quote): self
    {
        if ($quote->getPreMarketPrice() && $quote->getPreMarketTime() > $quote->getRegularMarketTime()) {
            return new self(
                Stock::PRICE_TYPE_PRE_MARKET,
                $quote->getExchange(),
                $quote->getSymbol(),
                $quote->getPreMarketPrice(),
                $quote->getCurrency(),
                $quote->getPreMarketChange(),
                $quote->getPreMarketTime()
            );
        }

        if ($quote->getPostMarketPrice() && $quote->getPostMarketTime() > $quote->getRegularMarketTime()) {
            return new self(
                Stock::PRICE_TYPE_POST_MARKET,
                $quote->getExchange(),
                $quote->getSymbol(),
                $quote->getPostMarketPrice(),
                $quote->getCurrency(),
                $quote->getPostMarketChange(),
                $quote->getPostMarketTime()
            );
        }

        return new self(
            Stock::PRICE_TYPE_REGULAR_MARKET,
            $quote->getExchange(),
            $quote->getSymbol(),
            $quote->getRegularMarketPrice(),
            $quote->getCurrency(),
            $quote->getRegularMarketChange(),
            $quote->getRegularMarketTime()
        );
    }

    public function __construct(string $market, string $exchange, string $symbol, float $price, string $currency, float $change, \DateTimeInterface $time)
    {
        $this->market = $market;
        $this->exchange = $exchange;
        $this->symbol = $symbol;
        $this->price = $price;
        $this->currency = $currency;
        $this->change = $change;
        $this->time = $time;
    }
}
