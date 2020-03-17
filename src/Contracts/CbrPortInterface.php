<?php

namespace Contracts;

interface CbrPortInterface
{
    /**
     * @return array
     */
    public function getIsoCodes(): array;

    /**
     * @param string $code
     * @param \DateTimeImmutable $date
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCurrencyRate(string $code, \DateTimeImmutable $date): array;
}