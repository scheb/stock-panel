<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Entity\Stock;
use App\Provider\StockUpdateEvent;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class StockAlertNotifier implements EventSubscriberInterface
{
    private const int ALERT_PERIOD_HOURS = 3;

    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        #[Autowire('%notification_sender%')]
        private readonly string $sender,
        #[Autowire('%notification_recipient%')]
        private readonly string $recipient,
    ) {
    }

    public function onStockUpdate(): void
    {
        $stocks = $this->stockRepository->findAll();
        foreach ($stocks as $stock) {
            /** @var Stock $stock */
            if (!($stock->getAlertComparator() && $stock->getAlertThreshold())) {
                continue;
            }

            // Minimum time between alerts
            if ($stock->getAlertLastTime() > new \DateTime("-".self::ALERT_PERIOD_HOURS."hours")) {
                continue;
            }

            if (
                ($stock->getAlertComparator() === Stock::COMPARATOR_ABOVE && $stock->getCurrentPrice() > $stock->getAlertThreshold())
                || ($stock->getAlertComparator() === Stock::COMPARATOR_BELOW && $stock->getCurrentPrice() < $stock->getAlertThreshold())
            ) {
                $this->notify($stock);
            }
        }
    }

    private function notify(Stock $stock): void
    {
        $text = $this->twig->render('Notifications/stockAlert.txt.twig', ['stock' => $stock]);
        $subject = substr($text, 0, strpos($text, "\n"));

        $email = new Email()
            ->from($this->sender)
            ->to($this->recipient)
            ->subject($subject)
            ->text($text);
        $this->mailer->send($email);

        $stock->setAlertLastTime(new \DateTime());
        $this->em->persist($stock);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StockUpdateEvent::class => 'onStockUpdate',
        ];
    }
}
