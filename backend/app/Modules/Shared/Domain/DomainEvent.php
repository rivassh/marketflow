<?php

namespace App\Modules\Shared\Domain;

interface DomainEvent
{
    public function getTimestamp(): \DateTimeImmutable;
}