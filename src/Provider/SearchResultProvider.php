<?php

namespace App\Provider;

use Scheb\YahooFinanceApi\ApiClient;

class SearchResultProvider
{
    /**
     * @var \Scheb\YahooFinanceApi\ApiClient
     */
    private $api;

    public function __construct(ApiClient $api)
    {
        $this->api = $api;
    }

    public function search(string $searchTerm): array
    {
        $data = $this->api->search($searchTerm);
        return $this->createResult($data);
    }

    private function createResult(array $data): array
    {
        $resultSet = [];
        foreach ($data as $result) {
            $resultSet[] = [
                'symbol' => $result->getSymbol(),
                'name' => $result->getName(),
                'exchange' => $result->getExchDisp() ?? null,
            ];
        }
        return $resultSet;
    }
}
