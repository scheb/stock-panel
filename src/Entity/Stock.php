<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'stock')]
#[ORM\Entity(repositoryClass: 'App\Repository\StockRepository')]
#[UniqueEntity('symbol')]
class Stock
{
    public const PRICE_TYPE_PRE_MARKET = 'pre';
    public const PRICE_TYPE_POST_MARKET = 'post';
    public const PRICE_TYPE_REGULAR_MARKET = 'regular';

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(name: 'symbol', type: 'string', length: 20, unique: true)]
    private string $symbol;

    #[ORM\Column(name: 'category', type: 'string', nullable: true)]
    private ?string $category;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(name: 'quantity', type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $quantity;

    #[ORM\Column(name: 'initialPrice', type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $initialPrice;

    #[ORM\Column(name: 'currentPrice', type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $currentPrice;

    #[ORM\Column(name: 'currentPriceMarket', type: 'string', nullable: true)]
    private string $currentPriceMarket;

    #[ORM\Column(name: 'currentPriceTime', type: 'datetime', nullable: true)]
    private \DateTimeInterface $currentPriceTime;

    #[ORM\Column(name: 'currentChange', type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $currentChange;

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

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
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

    public function getCurrentPrice(): ?float
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?float $currentPrice): self
    {
        $this->currentPrice = $currentPrice;
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
