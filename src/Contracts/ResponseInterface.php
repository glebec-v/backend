<?php

namespace Contracts;

interface ResponseInterface
{
    /**
     * @param array $rate
     * @param string $isoCode
     * @return string
     */
    public function createResponse(array $rate, string $isoCode): string;
}