<?php

namespace App\Provider;

use Scheb\YahooFinanceApi\ApiClient;

class SearchResultProvider
{
    public function __construct(
        private ApiClient $api
    ) {
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
