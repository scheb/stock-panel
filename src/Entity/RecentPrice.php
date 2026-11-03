<?php

declare(strict_types=1);

namespace App\Entity;

use Scheb\YahooFinanceApi\Results\Quote;

class RecentPrice
{
    public static function fromQuote(Quote $quote): ?self
    {
        if (
            null === $quote->getExchange()
            || null === $quote->getSymbol()
            || null === $quote->getCurrency()
        ) {
            return null;
        }

        if ($quote->getPreMarketPrice() && $quote->getPreMarketTime() && $quote->getPreMarketTime() > $quote->getRegularMarketTime()) {
            $priceLows = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketDayLow()], fn (?float $val) => null !== $val));
            $priceHighs = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketDayHigh()], fn (?float $val) => null !== $val));

            return new self(
                Stock::PRICE_TYPE_PRE_MARKET,
                $quote->getExchange(),
                $quote->getSymbol(),
                $quote->getPreMarketPrice(),
                min($priceLows),
                max($priceHighs),
                $quote->getCurrency(),
                $quote->getPreMarketChange() ?? 0.0,
                $quote->getPreMarketTime()
            );
        }

        if ($quote->getPostMarketPrice() && $quote->getPostMarketTime() && $quote->getPostMarketTime() > $quote->getRegularMarketTime()) {
            $priceLows = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getPostMarketPrice(), $quote->getRegularMarketDayLow()], fn (?float $val) => null !== $val));
            $priceHighs = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getPostMarketPrice(), $quote->getRegularMarketDayHigh()], fn (?float $val) => null !== $val));

            return new self(
                Stock::PRICE_TYPE_POST_MARKET,
                $quote->getExchange(),
                $quote->getSymbol(),
                $quote->getPostMarketPrice(),
                min($priceLows),
                max($priceHighs),
                $quote->getCurrency(),
                $quote->getPostMarketChange() ?? 0.0,
                $quote->getPostMarketTime()
            );
        }

        if (null === $quote->getRegularMarketPrice() || null === $quote->getRegularMarketTime()) {
            return null;
        }

        $priceLows = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketPrice(), $quote->getRegularMarketDayLow()], fn (?float $val) => null !== $val));
        $priceHighs = array_values(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketPrice(), $quote->getRegularMarketDayHigh()], fn (?float $val) => null !== $val));

        return new self(
            Stock::PRICE_TYPE_REGULAR_MARKET,
            $quote->getExchange(),
            $quote->getSymbol(),
            $quote->getRegularMarketPrice(),
            min($priceLows),
            max($priceHighs),
            $quote->getCurrency(),
            $quote->getRegularMarketChange() ?? 0.0,
            $quote->getRegularMarketTime()
        );
    }

    public function __construct(
        public string $market,
        public string $exchange,
        public string $symbol,
        public float $price,
        public float $priceLow,
        public float $priceHigh,
        public string $currency,
        public float $change,
        public \DateTimeInterface $time,
    ) {
    }
}
