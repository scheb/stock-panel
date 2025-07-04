<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Entity\Stock;
use App\Provider\StockEarningsProvider;
use App\Provider\StockUpdateEvent;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EarningsAlertNotifier
{
    private const int NOTIFY_WITHIN_DAYS = 3;
    private const int ALERT_PERIOD_DAYS = 3;

    public function __construct(
        private readonly StockEarningsProvider $stockEarningsProvider,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        #[Autowire('%notification_sender%')]
        private readonly string $sender,
        #[Autowire('%notification_recipient%')]
        private readonly string $recipient,
    ) {
    }

    public function notifyAboutUpcomingEarnings(): void
    {
        $notifyAboutEarnings = [];
        $earningsDateLimit = new \DateTime(self::NOTIFY_WITHIN_DAYS.' days');
        $alertDateLimit = new \DateTime("-" . self::ALERT_PERIOD_DAYS . " days");
        $stocks = $this->stockEarningsProvider->getStocksWithEarnings();
        foreach ($stocks as $stock) {
            if ($earningsDateLimit < $stock->getNextEarningsTime()) {
                break; // Since it's sorted, no need to check more stocks
            }

            // Minimum time between alerts
            if ($stock->getEarningsNotificationLastTime() > $alertDateLimit) {
                continue;
            }

            $notifyAboutEarnings[] = $stock;
        }

        if ($notifyAboutEarnings) {
            $this->notify($notifyAboutEarnings);
        }
    }

    private function notify(array $stocks): void
    {
        $text = $this->twig->render('Notifications/earningsAlert.txt.twig', ['stocks' => $stocks]);
        $subject = substr($text, 0, strpos($text, "\n"));

        $email = new Email()
            ->from($this->sender)
            ->to($this->recipient)
            ->subject($subject)
            ->text($text);
        $this->mailer->send($email);

        foreach ($stocks as $stock) {
            $stock->setEarningsNotificationLastTime(new \DateTime());
            $this->em->persist($stock);
        }
        $this->em->flush();
    }
}
