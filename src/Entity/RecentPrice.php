<?php

declare(strict_types=1);

namespace App\Entity;

use Scheb\YahooFinanceApi\Results\Quote;

class RecentPrice
{
    public static function fromQuote(Quote $quote): self
    {
        if ($quote->getPreMarketPrice() && $quote->getPreMarketTime() > $quote->getRegularMarketTime()) {
            return new self(
                Stock::PRICE_TYPE_PRE_MARKET,
                $quote->getExchange(),
                $quote->getSymbol(),
                $quote->getPreMarketPrice(),
                min(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketDayLow()])),
                max(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketDayHigh()])),
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
                min(array_filter([$quote->getPreMarketPrice(), $quote->getPostMarketPrice(), $quote->getRegularMarketDayLow()])),
                max(array_filter([$quote->getPreMarketPrice(), $quote->getPostMarketPrice(), $quote->getRegularMarketDayHigh()])),
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
            min(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketPrice(), $quote->getRegularMarketDayLow()])),
            max(array_filter([$quote->getPreMarketPrice(), $quote->getRegularMarketPrice(), $quote->getRegularMarketDayHigh()])),
            $quote->getCurrency(),
            $quote->getRegularMarketChange(),
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
        public float $change, public
        \DateTimeInterface $time,
    ) {
    }
}
