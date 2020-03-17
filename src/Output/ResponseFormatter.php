<?php

namespace Output;

use Contracts\ResponseInterface;

class ResponseFormatter implements ResponseInterface
{

    /**
     * @inheritDoc
     */
    public function createResponse(array $rate, string $isoCode): string
    {
        $current = reset($rate);
        $last = next($rate);

        // если нет котировки день назад, выдаем дельту 0.0
        if (false === $last) {
            $last = $current;
        }

        return sprintf('%s %f %f', $isoCode, $current, $current - $last);
    }
}