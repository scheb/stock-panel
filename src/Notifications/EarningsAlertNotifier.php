<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Entity\Stock;
use App\Provider\StockEarningsProvider;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Alarm\DisplayAction;
use Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
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

            $this->notify($stock);
            $stock->setEarningsNotificationLastTime(new \DateTime());
            $this->em->persist($stock);
        }

        $this->em->flush();
    }

    private function notify(Stock $stock): void
    {
        $text = $this->twig->render('Notifications/earningsAlert.txt.twig', ['stock' => $stock]);
        $subject = substr($text, 0, strpos($text, "\n"));

        $event = new Event();
        $eventTime = new DateTime($stock->getNextEarningsTime(), true);
        $event
            ->setSummary($stock->getName() . ' Earnings')
            ->setDescription($text)
            ->setOccurrence(new TimeSpan($eventTime, $eventTime))
            ->addAttendee(new Attendee(new EmailAddress($this->recipient)))
            ->addAlarm(new Alarm(
                new DisplayAction('Reminder: '.$stock->getName().' Earnings in 12 hours!'),
                new RelativeTrigger(DateInterval::createFromDateString('-12 hours'))->withRelationToEnd()
            ))
            ->addAlarm(new Alarm(
                new DisplayAction('Reminder: '.$stock->getName().' Earnings in 15 minutes!'),
                new RelativeTrigger(DateInterval::createFromDateString('-15 minutes'))->withRelationToEnd()
            ))
        ;

        $calendar = new Calendar([$event]);
        $ics = new CalendarFactory()->createCalendar($calendar);

        $email = new Email()
            ->from($this->sender)
            ->to($this->recipient)
            ->subject($subject)
            ->text($text);

        $attachment = new DataPart((string) $ics, 'invite.ics', 'text/calendar', 'quoted-printable');
        $attachment->asInline();
        $attachment->getHeaders()->addParameterizedHeader('Content-Type', 'text/calendar', ['charset' => 'utf-8', 'method' => 'REQUEST']);
        $email->addPart($attachment);

        $this->mailer->send($email);
    }
}
