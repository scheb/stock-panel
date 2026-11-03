<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RecentPrice;
use App\Entity\Stock;
use PHPUnit\Framework\TestCase;
use Scheb\YahooFinanceApi\Results\Quote;

class RecentPriceTest extends TestCase
{
    public function testFromQuoteWithCompleteRegularMarketData(): void
    {
        $time = new \DateTimeImmutable('2026-11-03 15:00:00');
        $quote = new Quote([
            'symbol' => 'AAPL',
            'exchange' => 'NMS',
            'currency' => 'USD',
            'regularMarketPrice' => 150.0,
            'regularMarketChange' => 2.5,
            'regularMarketDayLow' => 148.0,
            'regularMarketDayHigh' => 152.0,
            'regularMarketTime' => $time,
        ]);

        $recentPrice = RecentPrice::fromQuote($quote);

        $this->assertInstanceOf(RecentPrice::class, $recentPrice);
        $this->assertSame(Stock::PRICE_TYPE_REGULAR_MARKET, $recentPrice->market);
        $this->assertSame('AAPL', $recentPrice->symbol);
        $this->assertSame('NMS', $recentPrice->exchange);
        $this->assertSame('USD', $recentPrice->currency);
        $this->assertSame(150.0, $recentPrice->price);
        $this->assertSame(148.0, $recentPrice->priceLow);
        $this->assertSame(152.0, $recentPrice->priceHigh);
        $this->assertSame(2.5, $recentPrice->change);
        $this->assertSame($time, $recentPrice->time);
    }

    public function testFromQuoteWithNullPricesDoesNotThrowValueError(): void
    {
        $quote = new Quote([
            'symbol' => 'LU2573966905.SG',
            'exchange' => 'STU',
            'currency' => 'EUR',
            'regularMarketPrice' => null,
            'regularMarketDayLow' => null,
            'regularMarketDayHigh' => null,
            'preMarketPrice' => null,
            'postMarketPrice' => null,
            'regularMarketTime' => new \DateTimeImmutable(),
        ]);

        $this->assertNull(RecentPrice::fromQuote($quote));
    }

    public function testFromQuoteWithMissingRegularMarketTimeReturnsNull(): void
    {
        $quote = new Quote([
            'symbol' => 'AAPL',
            'exchange' => 'NMS',
            'currency' => 'USD',
            'regularMarketPrice' => 150.0,
            'regularMarketTime' => null,
        ]);

        $this->assertNull(RecentPrice::fromQuote($quote));
    }

    public function testFromQuoteWithMissingMetadataReturnsNull(): void
    {
        $quote = new Quote([
            'symbol' => null,
            'exchange' => 'NMS',
            'currency' => 'USD',
            'regularMarketPrice' => 150.0,
            'regularMarketTime' => new \DateTimeImmutable(),
        ]);

        $this->assertNull(RecentPrice::fromQuote($quote));
    }

    public function testFromQuoteWithNullDayLowAndDayHighFallsBackToRegularPrice(): void
    {
        $time = new \DateTimeImmutable('2026-11-03 15:00:00');
        $quote = new Quote([
            'symbol' => 'USDEUR=X',
            'exchange' => 'CCY',
            'currency' => 'EUR',
            'regularMarketPrice' => 0.89,
            'regularMarketChange' => 0.001,
            'regularMarketDayLow' => null,
            'regularMarketDayHigh' => null,
            'regularMarketTime' => $time,
        ]);

        $recentPrice = RecentPrice::fromQuote($quote);

        $this->assertInstanceOf(RecentPrice::class, $recentPrice);
        $this->assertSame(0.89, $recentPrice->price);
        $this->assertSame(0.89, $recentPrice->priceLow);
        $this->assertSame(0.89, $recentPrice->priceHigh);
    }

    public function testFromQuoteWithPreMarketData(): void
    {
        $regularTime = new \DateTimeImmutable('2026-11-03 15:00:00');
        $preMarketTime = new \DateTimeImmutable('2026-11-03 15:30:00');

        $quote = new Quote([
            'symbol' => 'AAPL',
            'exchange' => 'NMS',
            'currency' => 'USD',
            'regularMarketPrice' => 150.0,
            'regularMarketTime' => $regularTime,
            'preMarketPrice' => 155.0,
            'preMarketChange' => 5.0,
            'preMarketTime' => $preMarketTime,
            'regularMarketDayLow' => 149.0,
            'regularMarketDayHigh' => 151.0,
        ]);

        $recentPrice = RecentPrice::fromQuote($quote);

        $this->assertInstanceOf(RecentPrice::class, $recentPrice);
        $this->assertSame(Stock::PRICE_TYPE_PRE_MARKET, $recentPrice->market);
        $this->assertSame(155.0, $recentPrice->price);
        $this->assertSame(149.0, $recentPrice->priceLow);
        $this->assertSame(155.0, $recentPrice->priceHigh);
        $this->assertSame(5.0, $recentPrice->change);
        $this->assertSame($preMarketTime, $recentPrice->time);
    }

    public function testFromQuoteWithPostMarketData(): void
    {
        $regularTime = new \DateTimeImmutable('2026-11-03 15:00:00');
        $postMarketTime = new \DateTimeImmutable('2026-11-03 21:30:00');

        $quote = new Quote([
            'symbol' => 'AAPL',
            'exchange' => 'NMS',
            'currency' => 'USD',
            'regularMarketPrice' => 150.0,
            'regularMarketTime' => $regularTime,
            'postMarketPrice' => 158.0,
            'postMarketChange' => 8.0,
            'postMarketTime' => $postMarketTime,
            'regularMarketDayLow' => 149.0,
            'regularMarketDayHigh' => 151.0,
        ]);

        $recentPrice = RecentPrice::fromQuote($quote);

        $this->assertInstanceOf(RecentPrice::class, $recentPrice);
        $this->assertSame(Stock::PRICE_TYPE_POST_MARKET, $recentPrice->market);
        $this->assertSame(158.0, $recentPrice->price);
        $this->assertSame(149.0, $recentPrice->priceLow);
        $this->assertSame(158.0, $recentPrice->priceHigh);
        $this->assertSame(8.0, $recentPrice->change);
        $this->assertSame($postMarketTime, $recentPrice->time);
    }
}
