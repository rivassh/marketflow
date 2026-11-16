<?php

namespace App\Modules\Shared\ValueObjects;

class Price
{
    private int $amount;

    private string $currency;

    public function __construct(int $amount, string $currency = 'IRR')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price amount cannot be negative');
        }

        if (! in_array(strtoupper($currency), ['IRR', 'USD', 'EUR'], true)) {
            throw new \InvalidArgumentException('Unsupported currency');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromMinorUnits(int $amount, string $currency = 'IRR'): self
    {
        return new self($amount, $currency);
    }

    public static function fromMajorUnits(float $amount, string $currency = 'IRR'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getMajorUnits(): float
    {
        return $this->amount / 100;
    }

    public function format(string $locale = 'fa_IR'): string
    {
        return number_format($this->getMajorUnits(), 0, '.', ',').' '.$this->currency;
    }

    public function add(Price $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add prices with different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Price $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract prices with different currencies');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function equals(Price $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isLessThan(Price $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare prices with different currencies');
        }

        return $this->amount < $other->amount;
    }

    public function isGreaterThan(Price $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare prices with different currencies');
        }

        return $this->amount > $other->amount;
    }

    public function isBetween(Price $min, Price $max): bool
    {
        return ! $this->isLessThan($min) && ! $this->isGreaterThan($max);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'display' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
