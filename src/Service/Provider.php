<?php

namespace Service;

use Contracts\CbrPortInterface;
use Contracts\ProviderInterface;
use Exception\ProviderException;
use Psr\Cache\InvalidArgumentException as CacheCbrProviderException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Provider implements ProviderInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var CbrPortInterface
     */
    private $cbr;

    /**
     * Provider constructor.
     * @param CacheInterface $cache
     * @param CbrPortInterface $cbr
     */
    public function __construct(CacheInterface $cache, CbrPortInterface $cbr)
    {
        $this->cache = $cache;
        $this->cbr = $cbr;
    }

    /**
     * @param string $isoCode
     * @param \DateTimeImmutable $date
     *
     * @return array
     * @throws ProviderException
     */
    public function rate(string $isoCode, \DateTimeImmutable $date): array
    {
        try {
            $cbrCode = $this->cbrCodeFromIso($isoCode);
            $rate = $this->cbrRate($cbrCode, $date);
        } catch (CacheCbrProviderException $exception) {
            throw new ProviderException($exception->getMessage(), $exception->getCode());
        }

        return $rate;
    }

    /**
     * @param string $code
     * @param \DateTimeImmutable $date
     * @return array
     * @throws CacheCbrProviderException
     */
    private function cbrRate(string $code, \DateTimeImmutable $date): array
    {
        $this->cache->delete($code.'_'.$date->getTimestamp());
        /**
         * @param ItemInterface $item
         * @return array
         */
        $missed = function (ItemInterface $item) use ($code, $date) {
            $item->expiresAfter(\DateInterval::createFromDateString('1 day')); // чтобы не заморачиваться еще и с конфигом
            return $this->cbr->getCurrencyRate($code, $date);
        };

        return $this->cache->get($code.'_'.$date->getTimestamp(), $missed);
    }

    /**
     * @param string $isoCode
     * @return string
     * @throws CacheCbrProviderException
     */
    private function cbrCodeFromIso(string $isoCode): string
    {
        /**
         * Locking is built-in by default,
         * so you don't need to do anything beyond leveraging the Cache Contracts.
         * @see https://symfony.com/doc/current/components/cache.html#cache-contracts
         *
         * для упрощения, воспользуемся локом обновления кэша из коробки
         *
         * @param ItemInterface $item
         * @return array
         */
        $missed = function (ItemInterface $item) {
            $item->expiresAfter(\DateInterval::createFromDateString('1 day')); // чтобы не заморачиваться еще и с конфигом
            return $this->cbr->getIsoCodes();
        };
        $codes = $this->cache->get($isoCode, $missed);

        return $codes[$isoCode];
    }
}