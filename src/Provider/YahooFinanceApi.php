<?php

declare(strict_types=1);

namespace App\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
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

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getChartsData(string $symbol, string $range): array
    {
        if (!array_key_exists($range, self::RANGE_INTERVAL_MAP)) {
            throw new \InvalidArgumentException('Invalid range');
        }

        $interval = self::RANGE_INTERVAL_MAP[$range];
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . $symbol . "?range=" . $range . "&includePrePost=false&interval=" . $interval;
        $client = new Client();
        try {
            $response = $client->get($url);
        } catch (ClientException $e) {
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

    public function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
        ];
    }

    public function getNextEarningsDate(string $symbol): ?array
    {
        $qs = $this->getRandomQueryServer();

        // Initialize session cookies
        $cookieJar = $this->getCookies();

        // Get crumb value
        $crumb = $this->getCrumb($qs, $cookieJar);

        $modules = [
            'earnings',
        ];

        // Fetch quotes
        $url = 'https://query'.$qs.'.finance.yahoo.com/v10/finance/quoteSummary/'.urlencode($symbol).'?crumb='.$crumb.'&modules=earnings';
        $responseBody = (string) $this->client->request('GET', $url, ['cookies' => $cookieJar, 'headers' => $this->getHeaders()])->getBody();

        $earnings = json_decode($responseBody, true);
        if (isset($earnings['quoteSummary']['result'][0]['earnings']['earningsChart']['earningsDate'])) {
            return $earnings['quoteSummary']['result'][0]['earnings']['earningsChart']['earningsDate'];
        }

        return null;
    }

    private function getRandomQueryServer(): int
    {
        return rand(1, 2);
    }

    private function getCookies(): CookieJar
    {
        $cookieJar = new CookieJar();

        // Initialize session cookies
        $initialUrl = 'https://fc.yahoo.com';
        $this->client->request('GET', $initialUrl, ['cookies' => $cookieJar, 'http_errors' => false, 'headers' => $this->getHeaders()]);

        return $cookieJar;
    }

    private function getCrumb(int $qs, CookieJar $cookies): string
    {
        // Get crumb value
        $initialUrl = 'https://query'.(string) $qs.'.finance.yahoo.com/v1/test/getcrumb';

        return (string) $this->client->request('GET', $initialUrl, ['cookies' => $cookies, 'headers' => $this->getHeaders()])->getBody();
    }
}
