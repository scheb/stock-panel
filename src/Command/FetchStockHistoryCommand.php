<?php

declare(strict_types=1);

namespace App\Command;

use App\Provider\StockPriceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchStockHistoryCommand extends Command
{
    public function __construct(
        private readonly StockPriceProvider $stockPriceProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('stock:fetch-history')
            ->setDescription('Fetch stock history')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stockPriceProvider->updateStocksHistory();
        $output->writeln('Stocks history updated successfully!');
        return Command::SUCCESS;
    }
}
