<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: "product")]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: "decimal", precision: 8, scale: 2)]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: "boolean")]
    private bool $isFeatured = false;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $featuredRank = null;

    #[ORM\Column(type: "integer")]
    private int $stockXS = 2;

    #[ORM\Column(type: "integer")]
    private int $stockS = 2;

    #[ORM\Column(type: "integer")]
    private int $stockM = 2;

    #[ORM\Column(type: "integer")]
    private int $stockL = 2;

    #[ORM\Column(type: "integer")]
    private int $stockXL = 2;

    // Getters & Setters //
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getIsFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getFeaturedRank(): ?int
    {
        return $this->featuredRank;
    }

    public function setFeaturedRank(?int $featuredRank): static
    {
        $this->featuredRank = $featuredRank;
        return $this;
    }
    public function getStockXS(): int
    {
        return $this->stockXS;
    }

    public function setStockXS(int $stockXS): static
    {
        $this->stockXS = $stockXS;
        return $this;
    }

    public function getStockS(): int
    {
        return $this->stockS;
    }

    public function setStockS(int $stockS): static
    {
        $this->stockS = $stockS;
        return $this;
    }

    public function getStockM(): int
    {
        return $this->stockM;
    }

    public function setStockM(int $stockM): static
    {
        $this->stockM = $stockM;
        return $this;
    }

    public function getStockL(): int
    {
        return $this->stockL;
    }

    public function setStockL(int $stockL): static
    {
        $this->stockL = $stockL;
        return $this;
    }

    public function getStockXL(): int
    {
        return $this->stockXL;
    }

    public function setStockXL(int $stockXL): static
    {
        $this->stockXL = $stockXL;
        return $this;
    }
}
