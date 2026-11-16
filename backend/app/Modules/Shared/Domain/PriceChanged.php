<?php

namespace App\Modules\Shared\Domain;

use App\Modules\Shared\ValueObjects\Price;

class PriceChanged implements DomainEvent
{
    private Product $product;

    private Price $previousPrice;

    private Price $newPrice;

    private \DateTimeImmutable $occurredAt;

    public function __construct(Product $product, Price $previousPrice, Price $newPrice)
    {
        $this->product = $product;
        $this->previousPrice = $previousPrice;
        $this->newPrice = $newPrice;
        $this->occurredAt = new \DateTimeImmutable;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getPreviousPrice(): Price
    {
        return $this->previousPrice;
    }

    public function getNewPrice(): Price
    {
        return $this->newPrice;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getPriceDifference(): Price
    {
        return $this->previousPrice->subtract($this->newPrice);
    }

    public function wasPriceIncreased(): bool
    {
        return $this->previousPrice->isLessThan($this->newPrice);
    }

    public function wasPriceDecreased(): bool
    {
        return $this->previousPrice->isGreaterThan($this->newPrice);
    }
}
