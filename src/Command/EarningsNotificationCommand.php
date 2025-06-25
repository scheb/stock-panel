<?php

declare(strict_types=1);

namespace App\Command;

use App\Notifications\EarningsAlertNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EarningsNotificationCommand extends Command
{
    public function __construct(
        private readonly EarningsAlertNotifier $earningsAlertNotifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('stock:notify-earnings')
            ->setDescription('Notify about upcoming earnings');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->earningsAlertNotifier->notifyAboutUpcomingEarnings();
        $output->writeln('Notified about upcoming earnings!');
        return Command::SUCCESS;
    }
}
