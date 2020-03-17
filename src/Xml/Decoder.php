<?php

namespace Xml;

use Exception\DecoderException;

class Decoder
{
    /** @var array */
    private $xmlArray;

    /**
     * @param string $xmlString
     * @return static
     * @throws DecoderException
     */
    public static function fromXmlString(string $xmlString): self
    {
        $xmlString = str_replace(["\n", "\r", "\t"], '', $xmlString);
        $xmlString = trim(str_replace('"', "'", $xmlString));
        $simpleXml = simplexml_load_string($xmlString);
        if (false === $simpleXml) {
            throw new DecoderException('loading xml failed');
        }
        $json = json_encode($simpleXml);
        if (false === $json) {
            throw new DecoderException('xml is not valid: '. json_last_error_msg(), json_last_error_msg());
        }

        return new self(json_decode($json, true));
    }

    /**
     * Decoder constructor.
     * @param array $xml
     */
    private function __construct(array $xml)
    {
        $this->xmlArray = $xml;
    }

    /**
     * @return array
     * @throws DecoderException
     */
    public function readISOCodes(): array
    {
        $ret = [];
        $items = $this->xmlArray['Item'] ?? [];
        foreach ($items as $currencyData) {
            if (!isset($currencyData['@attributes']) || !isset($currencyData['@attributes']['ID'])) {
                throw new DecoderException('missing CBR ID');
            }
            if (!isset($currencyData['ISO_Char_Code'])) {
                throw new DecoderException('missing ISO code');
            }

            $cbrCode = $currencyData['@attributes']['ID'];
            $ISOCode = $currencyData['ISO_Char_Code'];
            if (is_array($ISOCode)) {
                continue;
            }
            $ret[$ISOCode] = $cbrCode;
        }

        return $ret;
    }

    /**
     * @return array
     * @throws DecoderException
     */
    public function readCurrencyRate(): array
    {
        $ret = [];
        $records = $this->xmlArray['Record'] ?? [];
        if (0 !== count($records)) {
            if (array_key_exists(0, $records)) {
                foreach ($records as $rate) {
                    $this->parse($rate, $ret);
                }
            } else {
                $this->parse($records, $ret);
            }

            uksort($ret, function ($a, $b) {
                $dateA = new \DateTimeImmutable($a);
                $dateB = new \DateTimeImmutable($b);
                return $dateB <=> $dateA;
            });
        }

        return array_values($ret);
    }

    /**
     * @param array $data
     * @param $ret
     * @throws DecoderException
     */
    private function parse(array $data, &$ret): void
    {
        if (
            !isset($data['@attributes']) ||
            !isset($data['@attributes']['Date']) ||
            !isset($data['Value']) ||
            !isset($data['Nominal'])
        ) {
            throw new DecoderException('unexpected rate format');
        }
        $ret[$data['@attributes']['Date']] = (float)str_replace(',', '.', $data['Value'])/(float)$data['Nominal'];
    }

}