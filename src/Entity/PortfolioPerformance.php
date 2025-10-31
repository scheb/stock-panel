<?php

declare(strict_types=1);

namespace App\Entity;

use App\Provider\StockPriceProvider;

class PortfolioPerformance
{
    private float $investment = 0;
    private float $currentValue = 0;
    private float $profitSinceLastDay = 0;

    public function __construct(
        private readonly StockPriceProvider $stockPriceProvider,
        /** @var Stock[] $stocks */
        private readonly array $stocks,
        private readonly string $currency,
    ) {
        $this->investment = 0;
        $this->currentValue = 0;
        $this->profitSinceLastDay = 0;
        foreach ($this->stocks as $stock) {
            $originalValue = $stock->getInvestment();
            $currentValue = $stock->getCurrentValue();
            if (null !== $currentValue && null !== $originalValue) {
                if ($stock->getCurrency() !== $this->currency) {
                    $originalValue = $this->stockPriceProvider->convertPrice($originalValue, $stock->getCurrency(), $this->currency);
                    $currentValue = $this->stockPriceProvider->convertPrice($currentValue, $stock->getCurrency(), $this->currency);
                }
                $this->investment += $originalValue;
                $this->currentValue += $currentValue;
            }

            $profitSinceLastDay = $stock->getProfitSinceLastDay();
            if (null !== $profitSinceLastDay) {
                $this->profitSinceLastDay += $profitSinceLastDay;
            }
        }
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getInvestment(): float
    {
        return $this->investment;
    }

    public function getCurrentValue(): float
    {
        return $this->currentValue;
    }

    public function getProfitIndicator(): string
    {
        return $this->getProfit() < 0 ? Stock::INDICATOR_DOWN : Stock::INDICATOR_UP;
    }

    public function getProfit(): float
    {
        return $this->currentValue - $this->investment;
    }

    public function getProfitPercent(): float
    {
        return $this->getProfit() / $this->investment;
    }

    public function getProfitSinceLastDayPercent(): float
    {
        return $this->profitSinceLastDay / ($this->getProfit() - $this->profitSinceLastDay);
    }

    public function getProfitSinceLastDay(): float
    {
        return $this->profitSinceLastDay;
    }

    public function getProfitSinceLastDayIndicator(): string
    {
        return $this->profitSinceLastDay < 0 ? Stock::INDICATOR_DOWN : Stock::INDICATOR_UP;
    }
}
