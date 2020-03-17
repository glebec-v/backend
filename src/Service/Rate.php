<?php

namespace Service;

use Contracts\ProviderInterface;
use Contracts\ResponseInterface;

class Rate
{
    /**
     * @var ProviderInterface
     */
    private $rateProvider;

    /**
     * @var ResponseInterface
     */
    private $formatter;

    /**
     * Rate constructor.
     * @param ProviderInterface $rateProvider
     * @param ResponseInterface $formatter
     */
    public function __construct(ProviderInterface $rateProvider, ResponseInterface $formatter)
    {
        $this->rateProvider = $rateProvider;
        $this->formatter = $formatter;
    }

    /**
     * @param \DateTimeImmutable $date
     * @param $isoCode
     * @param string $baseCode
     * @return string
     */
    public function getRate(\DateTimeImmutable $date, $isoCode, $baseCode = '')
    {
        if (empty($baseCode)) {
            return $this->formatter->createResponse(
                $this->rateProvider->rate($isoCode, $date),
                $isoCode
            );
        } else {
            $targetRate = $this->rateProvider->rate($isoCode, $date);
            $baseRate = $this->rateProvider->rate($baseCode, $date);
            $crossRate = [];
            $count = count($targetRate);
            for ($i = 0; $i < $count; $i++) {
                $crossRate[$i] = $targetRate[$i] / $baseRate[$i];
            }

            return $this->formatter->createResponse($crossRate, sprintf('%s/%s', $isoCode, $baseCode));
        }
    }

}