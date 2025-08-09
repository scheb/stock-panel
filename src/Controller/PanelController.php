<?php

namespace App\Controller;

use App\Entity\PortfolioPerformance;
use App\Entity\Stock;
use App\Provider\StockPriceProvider;
use App\Provider\YahooFinanceApi;
use App\Repository\StockHistoryRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

class PanelController extends AbstractController
{
    public function __construct(
        private readonly StockPriceProvider     $stockPriceProvider,
        private readonly StockRepository        $stockRepo,
        private readonly StockHistoryRepository $stockHistoryRepo,
        private readonly YahooFinanceApi        $financeApi,
    ) {
    }

    /**
     * Show the stock panel
     */
    #[Route(path: '/', name: 'stock_table')]
    public function tableAction(): Response
    {
        if ($this->stockPriceProvider->hasToUpdate()) {
            $this->updateStockPrices();
        }

        return $this->render("Panel/table.html.twig", $this->getStockTableVariables());
    }

    private function getStockTableVariables(): array
    {
        $stockCategories = $this->stockPriceProvider->getCategorizedStocks();
        $stocks = array_merge(... array_values($stockCategories));
        $updateDates = array_map(fn(Stock $stock) => $stock->getUpdatedAt(), $stocks);
        $lastUpdateDate = max($updateDates);

        return [
            'categories' => $stockCategories,
            'performance' => new PortfolioPerformance($this->stockPriceProvider, $stocks, 'EUR'),
            'lastUpdateDate' => $lastUpdateDate,
        ];
    }

    #[Route(path: '/change-history/{id}.svg', name: 'stock_change_history')]
    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    public function getChangeHistorySVG(int $id): Response
    {
        $stock = $this->getStock($id);
        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }

        $changeHistory = $this->stockHistoryRepo->getChangeHistory($stock);
        $response = $this->render("Panel/changeHistory.svg.twig", [
            'changeHistory' => $changeHistory,
        ]);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    /**
     * Show the stock panel
     */
    #[Route(path: '/charts', name: 'stock_charts')]
    public function chartsAction(): Response
    {
        if ($this->stockPriceProvider->hasToUpdate()) {
            $this->updateStockPrices();
        }

        $stocks = $this->stockPriceProvider->getStocks();
        return $this->render("Panel/charts.html.twig", [
            'stocks' => $stocks,
        ]);
    }

    /**
     * Get JSON data for the chart
     */
    #[Route(path: '/charts/{id}/{range}.json', name: 'stock_charts_data')]
    public function getChartData(int $id, string $range): Response
    {
        $stock = $this->getStock($id);
        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }

        $symbol = $stock->getCurrentPriceSymbol();
        if (!$symbol) {
            // Fallback when there is no current price set
            $symbol = $stock->getSymbols()[0] ?? null;
        }

        $chartsData = $this->financeApi->getChartsData($symbol, $range);

        return new JsonResponse($chartsData);
    }

    /**
     * Force stock update
     */
    #[Route(path: '/update', name: 'stock_update')]
    public function updateAction(): Response
    {
        try {
            $this->stockPriceProvider->updateStocks();
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }

        return $this->render("Panel/tableContent.html.twig", $this->getStockTableVariables());
    }

    private function getStock(int $id): ?Stock
    {
        return $this->stockRepo->findOneById($id);
    }

    private function updateStockPrices(): void
    {
        try {
            $this->stockPriceProvider->updateStocks();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Could not update stock prices: '.$e->getMessage());
        }
    }
}
