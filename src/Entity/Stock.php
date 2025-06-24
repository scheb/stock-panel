<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'stock')]
#[ORM\Entity(repositoryClass: 'App\Repository\StockRepository')]
class Stock
{
    public const string PRICE_TYPE_PRE_MARKET = 'pre';
    public const string PRICE_TYPE_POST_MARKET = 'post';
    public const string PRICE_TYPE_REGULAR_MARKET = 'regular';
    public const string COMPARATOR_ABOVE = 'above';
    public const string COMPARATOR_BELOW = 'below';

    private const string INDICATOR_UP = 'up';
    private const string INDICATOR_DOWN = 'down';
    private const string INDICATOR_NEUTRAL = 'neutral';
    private const string INDICATOR_STRONG = 'strong';
    private const string INDICATOR_VERY_STRONG = 'very-strong';
    private const float PROFIT_THRESHOLD = 0.1;
    private const float CHANGE_THRESHOLD = 0.005;
    private const float STRONG_CHANGE_THRESHOLD = 0.03;
    private const float VERY_STRONG_CHANGE_THRESHOLD = 0.06;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(name: 'symbols', type: 'simple_array')]
    private array $symbols;

    #[ORM\Column(name: 'category', type: 'string', nullable: true)]
    private ?string $category;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(name: 'quantity', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $quantity;

    #[ORM\Column(name: 'initialPrice', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $initialPrice;

    #[ORM\Column(name: 'lastDayPrice', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $lastDayPrice;

    #[ORM\Column(name: 'lastDayPriceTime', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastDayPriceTime;

    #[ORM\Column(name: 'currentPrice', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $currentPrice;

    #[ORM\Column(name: 'currentPriceSymbol', type: 'string', nullable: true)]
    private ?string $currentPriceSymbol;

    #[ORM\Column(name: 'currentPriceExchange', type: 'string', nullable: true, enumType: Exchange::class)]
    private ?Exchange $currentPriceExchange;

    #[ORM\Column(name: 'currentPriceMarket', type: 'string', nullable: true)]
    private ?string $currentPriceMarket;

    #[ORM\Column(name: 'currentPriceTime', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $currentPriceTime;

    #[ORM\Column(name: 'currentChange', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $currentChange;

    #[ORM\Column(name: 'alertThreshold', type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?float $alertThreshold;

    #[ORM\Column(name: 'alertDynamicThresholdPercent', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?float $alertDynamicThresholdPercent;

    #[ORM\Column(name: 'alertComparator', type: 'string', nullable: true)]
    private ?string $alertComparator;

    #[ORM\Column(name: 'alertLastTime', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $alertLastTime;

    #[ORM\Column(name: 'createdAt', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt;

    #[ORM\Column(name: 'displayChart', type: 'boolean')]
    private bool $displayChart = true;

    #[ORM\Column(name: 'favourite', type: 'boolean')]
    private bool $favourite = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    ////////////////////////////////////////////////////////////////////////////////////////// CONVENIENCE

    /**
     * Return invested money
     */
    public function getInvestment(): ?float
    {
        if ($this->quantity && $this->initialPrice) {
            return $this->quantity * $this->initialPrice;
        } else {
            return null;
        }
    }

    /**
     * Get current value of investment
     */
    public function getCurrentValue(): ?float
    {
        if ($this->quantity && $this->initialPrice && $this->currentPrice) {
            return $this->quantity * $this->currentPrice;
        } else {
            return null;
        }
    }

    /**
     * Return profit
     */
    public function getProfit(): ?float
    {
        if ($this->quantity && $this->initialPrice && $this->currentPrice) {
            return $this->getCurrentValue() - $this->getInvestment();
        } else {
            return null;
        }
    }

    /**
     * Return profit percentage
     */
    public function getProfitPercent(): float
    {
        return $this->getProfit() / $this->getInvestment();
    }

    /**
     * Get percent of current change
     */
    public function getCurrentChangePercent(): float
    {
        $oldPrice = $this->currentPrice - $this->currentChange;
        if ($oldPrice) {
            return $this->currentChange / $oldPrice;
        }
        return 0;
    }

    public function getChangeSinceLastDayPercent(): float
    {
        if (null !== $this->lastDayPrice) {
            return $this->getChangeSinceLastDay() / $this->lastDayPrice;
        }
        return 0;
    }

    public function getChangeSinceLastDay(): float
    {
        if (null !== $this->lastDayPrice && null !== $this->currentPrice) {
            return $this->currentPrice - $this->lastDayPrice;
        }
        return 0;
    }

    public function getProfitIndicator(): string
    {
        if ($this->getProfitPercent() > self::PROFIT_THRESHOLD) {
            return self::INDICATOR_UP;
        }
        if ($this->getProfitPercent() < -self::PROFIT_THRESHOLD) {
            return self::INDICATOR_DOWN;
        }

        return self::INDICATOR_NEUTRAL;
    }

    public function getChangeSinceLastDayIndicator(): string
    {
        if ($this->getChangeSinceLastDayPercent() > self::VERY_STRONG_CHANGE_THRESHOLD) {
            return self::INDICATOR_UP . ' ' . self::INDICATOR_VERY_STRONG;
        }
        if ($this->getChangeSinceLastDayPercent() > self::STRONG_CHANGE_THRESHOLD) {
            return self::INDICATOR_UP . ' ' . self::INDICATOR_STRONG;
        }
        if ($this->getChangeSinceLastDayPercent() > self::CHANGE_THRESHOLD) {
            return self::INDICATOR_UP;
        }

        if ($this->getChangeSinceLastDayPercent() < -self::VERY_STRONG_CHANGE_THRESHOLD) {
            return self::INDICATOR_DOWN . ' ' . self::INDICATOR_VERY_STRONG;
        }
        if ($this->getChangeSinceLastDayPercent() < -self::STRONG_CHANGE_THRESHOLD) {
            return self::INDICATOR_DOWN . ' ' . self::INDICATOR_STRONG;
        }
        if ($this->getChangeSinceLastDayPercent() < -self::CHANGE_THRESHOLD) {
            return self::INDICATOR_DOWN;
        }

        return self::INDICATOR_NEUTRAL;
    }

    ////////////////////////////////////////////////////////////////////////////////////////// GETTER / SETTER


    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getSymbols(): array
    {
        return $this->symbols;
    }

    /**
     * @param string[] $symbols
     * @return $this
     */
    public function setSymbols(array $symbols): self
    {
        $this->symbols = $symbols;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getInitialPrice(): ?float
    {
        return $this->initialPrice;
    }

    public function setInitialPrice(?float $initialPrice): self
    {
        $this->initialPrice = $initialPrice;
        return $this;
    }

    public function getLastDayPrice(): ?float
    {
        return $this->lastDayPrice;
    }

    public function setLastDayPrice(?float $lastDayPrice): self
    {
        $this->lastDayPrice = $lastDayPrice;
        return $this;
    }

    public function getLastDayPriceTime(): ?\DateTimeInterface
    {
        return $this->lastDayPriceTime;
    }

    public function setLastDayPriceTime(?\DateTimeInterface $lastDayPriceTime): self
    {
        $this->lastDayPriceTime = $lastDayPriceTime;
        return $this;
    }

    public function getCurrentPrice(): ?float
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?float $currentPrice): self
    {
        $this->currentPrice = $currentPrice;
        return $this;
    }

    public function getCurrentPriceSymbol(): ?string
    {
        return $this->currentPriceSymbol;
    }

    public function setCurrentPriceSymbol(?string $currentPriceSymbol): self
    {
        $this->currentPriceSymbol = $currentPriceSymbol;
        return $this;
    }

    public function getCurrentPriceExchange(): ?Exchange
    {
        return $this->currentPriceExchange;
    }

    public function setCurrentPriceExchange(?Exchange $currentPriceExchange): self
    {
        $this->currentPriceExchange = $currentPriceExchange;
        return $this;
    }

    public function getCurrentPriceMarket(): ?string
    {
        return $this->currentPriceMarket;
    }

    public function setCurrentPriceMarket(?string $currentPriceMarket): self
    {
        $this->currentPriceMarket = $currentPriceMarket;
        return $this;
    }

    public function getCurrentPriceTime(): ?\DateTimeInterface
    {
        return $this->currentPriceTime;
    }

    public function setCurrentPriceTime(?\DateTimeInterface $currentPriceTime): self
    {
        $this->currentPriceTime = $currentPriceTime;
        return $this;
    }

    public function getCurrentChange(): ?float
    {
        return $this->currentChange;
    }

    public function setCurrentChange(?float $currentChange): self
    {
        $this->currentChange = $currentChange;
        return $this;
    }

    public function getAlertThreshold(): ?float
    {
        return $this->alertThreshold;
    }

    public function setAlertThreshold(?float $alertThreshold): self
    {
        $this->alertThreshold = $alertThreshold;
        return $this;
    }

    public function getAlertDynamicThresholdPercent(): ?float
    {
        return $this->alertDynamicThresholdPercent;
    }

    public function setAlertDynamicThresholdPercent(?float $alertDynamicThresholdPercent): self
    {
        $this->alertDynamicThresholdPercent = $alertDynamicThresholdPercent;
        return $this;
    }

    public function getAlertComparator(): ?string
    {
        return $this->alertComparator;
    }

    public function setAlertComparator(?string $alertComparator): self
    {
        $this->alertComparator = $alertComparator;
        return $this;
    }

    public function getAlertLastTime(): ?\DateTimeInterface
    {
        return $this->alertLastTime;
    }

    public function setAlertLastTime(?\DateTimeInterface $alertLastTime): self
    {
        $this->alertLastTime = $alertLastTime;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isDisplayChart(): bool
    {
        return $this->displayChart;
    }

    public function setDisplayChart(bool $displayChart): self
    {
        $this->displayChart = $displayChart;
        return $this;
    }

    public function isFavourite(): bool
    {
        return $this->favourite;
    }

    public function setFavourite(bool $favourite): self
    {
        $this->favourite = $favourite;
        return $this;
    }
}
