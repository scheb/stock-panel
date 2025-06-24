<?php

declare(strict_types=1);

namespace App\Command;

use App\Provider\StockEarningsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateEarningsTimesCommand extends Command
{
    public function __construct(
        private readonly StockEarningsProvider $stockEarningsProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('stock:update-earnings')
            ->setDescription('Update earnings times');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stockEarningsProvider->updateStockEarningTimes();
        $output->writeln('Stocks earnings times successfully!');
        return Command::SUCCESS;
    }
}
