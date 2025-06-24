<?php

declare(strict_types=1);

namespace App\Controller;

use App\Provider\StockEarningsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EarningsController extends AbstractController
{
    public function __construct(
        private StockEarningsProvider $stockEarningsProvider,
    ) {
    }

    #[Route(path: '/earnings', name: 'stock_earnings')]
    public function displayEarnings(): Response
    {
        $stocksWithEarnings = $this->stockEarningsProvider->getStocksWithEarnings();

        return $this->render('Earnings/earnings.html.twig', [
            'stocks' => $stocksWithEarnings
        ]);
    }
}
