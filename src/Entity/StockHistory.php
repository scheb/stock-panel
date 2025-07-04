<?php

namespace App\Entity;

use App\Repository\StockHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'stock_history')]
#[ORM\Entity(repositoryClass: StockHistoryRepository::class)]
#[ORM\UniqueConstraint(name: "unique_history", columns: ["stock_id", "symbol", "date"])]
class StockHistory
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Stock::class)]
    #[ORM\JoinColumn(name: 'stock_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Stock $stock;

    #[ORM\Column(name: 'symbol', type: 'string', length: 20)]
    private string $symbol;

    #[ORM\Column(name: 'date', type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(name: 'price', type: 'decimal', precision: 12, scale: 6)]
    private float $price;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function setStock(Stock $stock): self
    {
        $this->stock = $stock;
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

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }
}
