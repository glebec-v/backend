<?php

namespace CbrPort;

use Contracts\CbrPortInterface;
use Exception\CbrPortException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xml\Decoder;

class CbrPort implements CbrPortInterface
{
    private $client;

    /**
     * CbrPort constructor.
     * @param HttpClientInterface $client
     */
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     * @throws CbrPortException
     * @throws \Exception\DecoderException
     */
    public function getIsoCodes(): array
    {
        $content = '';
        try {
            $response = $this->client->request(
                'GET',
                'http://www.cbr.ru/scripts/XML_valFull.asp',
                [
                    'query' => [
                        'd' => 0
                    ],
                ]
            );
            $content = $response->getContent();
        } catch (HttpExceptionInterface $exception) {
            throw new CbrPortException($exception->getMessage(), $exception->getCode());
        } catch (TransportExceptionInterface $exception) {
            throw new CbrPortException($exception->getMessage(), $exception->getCode());
        }

        return Decoder::fromXmlString($content)->readISOCodes();
    }

    /**
     * @param string $code
     * @param \DateTimeImmutable $date
     * @return array
     * @throws CbrPortException
     * @throws \Exception\DecoderException
     */
    public function getCurrencyRate(string $code, \DateTimeImmutable $date): array
    {
        $content = '';
        try {
            $response = $this->client->request(
                'GET',
                'http://www.cbr.ru/scripts/XML_dynamic.asp',
                [
                    'query' => [
                        'date_req1' => $date->sub(new \DateInterval('P1D'))->format('d/m/Y'),
                        'date_req2' => $date->format('d/m/Y'),
                        'VAL_NM_RQ' => $code
                    ],
                ]
            );
            $content = $response->getContent();
        } catch (HttpExceptionInterface $exception) {
            throw new CbrPortException($exception->getMessage(), $exception->getCode());
        } catch (TransportExceptionInterface $exception) {
            throw new CbrPortException($exception->getMessage(), $exception->getCode());
        }

        return Decoder::fromXmlString($content)->readCurrencyRate();
    }

}