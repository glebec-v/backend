<?php

namespace Contracts;

interface ProviderInterface
{
    public function rate(string $isoCode, \DateTimeImmutable $date): array;
}