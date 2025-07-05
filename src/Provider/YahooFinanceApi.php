<?php

declare(strict_types=1);

namespace App\Provider;

use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Context\ContextManagerInterface;
use Scheb\YahooFinanceApi\Exception\ApiException;

class YahooFinanceApi
{
    private const array RANGE_INTERVAL_MAP = [
        '1d' => '2m',
        '5d' => '15m',
        '1mo' => '1h',
        '6mo' => '1d',
        'ytd' => '1wk',
        '1y' => '1wk',
        '5y' => '1mo',
        'max' => '1mo',
    ];

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly ContextManagerInterface $sessionManager,
    ) {
    }

    public function getChartsData(string $symbol, string $range): array
    {
        if (!array_key_exists($range, self::RANGE_INTERVAL_MAP)) {
            throw new \InvalidArgumentException('Invalid range');
        }

        $interval = self::RANGE_INTERVAL_MAP[$range];
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . $symbol . "?range=" . $range . "&includePrePost=false&interval=" . $interval;
        try {
            $response = $this->sessionManager->request('GET', $url);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }

        $json = json_decode((string) $response->getBody(), true);
        if (!isset($json['chart']['result'][0]['timestamp'])) {
            throw new ApiException('Timestamps not found');
        }
        if (!isset($json['chart']['result'][0]['indicators']['quote'][0]['close'])) {
            throw new ApiException('Closing pricees not found');
        }

        $dataPrice = [];
        $dataVolume = [];
        $timestamps = $json['chart']['result'][0]['timestamp'];
        $openPrices = $json['chart']['result'][0]['indicators']['quote'][0]['open'];
        $highPrices = $json['chart']['result'][0]['indicators']['quote'][0]['high'];
        $lowPrices = $json['chart']['result'][0]['indicators']['quote'][0]['low'];
        $closingPrices = $json['chart']['result'][0]['indicators']['quote'][0]['close'];
        $volumes = $json['chart']['result'][0]['indicators']['quote'][0]['volume'];
        foreach ($timestamps as $index => $timestamp) {
            $timestampMs = $timestamp * 1000;
            $openPrice = $openPrices[$index] ?? null;
            $highPrice = $highPrices[$index] ?? null;
            $lowPrice = $lowPrices[$index] ?? null;
            $closingPrice = $closingPrices[$index] ?? null;
            $volume = $volumes[$index] ?? null;
            $dataPrice[] = [$timestampMs, $openPrice, $highPrice, $lowPrice, $closingPrice];
            $dataVolume[] = [$timestampMs, $volume];
        }

        return [
            'price' => $dataPrice,
            'volume' => $dataVolume,
        ];
    }

    public function getNextEarningsDate(string $symbol): ?array
    {
        $earnings = $this->apiClient->getStockSummary($symbol, ['earnings']);

        return $earnings[0]['earnings']['earningsChart']['earningsDate'] ?? null;
    }
}
