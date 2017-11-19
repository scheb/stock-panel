<?php

namespace App\Controller;

use App\Provider\StockPriceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanelController extends Controller
{
    /**
     * Show the stock panel
     * @Route("/", name="stock_panel")
     */
    public function indexAction(): Response
    {
        $stockProvider = $this->getStockPriceProvider();
        $stocks = $stockProvider->getStocksAndUpdate();
        return $this->render("Panel/index.html.twig", [
            'stocks' => $stocks,
        ]);
    }

    /**
     * Show the stock panel
     * @Route("/charts", name="stock_charts")
     */
    public function chartsAction(): Response
    {
        $stockProvider = $this->getStockPriceProvider();
        $stocks = $stockProvider->getStocksAndUpdate();
        return $this->render("Panel/charts.html.twig", [
            'stocks' => $stocks,
        ]);
    }

    /**
     * Force stock update
     * @Route("/update", name="stock_update")
     */
    public function updateAction(): Response
    {
        $stockProvider = $this->getStockPriceProvider();
        $stockProvider->updateStocks();
        $stocks = $stockProvider->getStocks();
        return $this->render("Panel/table.html.twig", [
            'stocks' => $stocks,
            'privacyMode' => true,
        ]);
    }

    private function getStockPriceProvider(): StockPriceProvider
    {
        return $this->get(StockPriceProvider::class);
    }
}
