<?php

declare(strict_types=1);

namespace App\Command;

use App\Provider\StockPriceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStocksCommand extends Command
{
    public function __construct(
        private readonly StockPriceProvider $stockPriceProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('stock:update')
            ->setDescription('Update stocks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stockPriceProvider->updateStocks();
        $output->writeln('Stocks updated successfully!');
        return Command::SUCCESS;
    }
}
