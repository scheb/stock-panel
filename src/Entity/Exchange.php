<?php

declare(strict_types=1);

namespace App\Entity;

enum Exchange: string
{
    case FRANKFURT = 'FRA';
    case XETRA = 'GER';
    case NASDAQ = 'NMS';
    case NYSE = 'NYQ';
    case PKN = 'PKN';
    case NASDAQ_CM = 'NCM';
    case STUTTGART = 'STU';
    case LONDON = 'LSE';

    public function label(): string
    {
        return match($this)
        {
            self::FRANKFURT => 'Frankfurt',
            self::XETRA => 'Xetra',
            self::NASDAQ => 'Nasdaq',
            self::NYSE => 'NYSE',
            self::PKN => 'OTC Markets OTCPK',
            self::NASDAQ_CM => 'NasdaqCM',
            self::STUTTGART => 'Stuttgart',
            self::LONDON => 'London',
        };
    }

    public function flag(): string
    {
        return match($this)
        {
            self::FRANKFURT => '🇩🇪',
            self::XETRA => '🇩🇪',
            self::NASDAQ => '🇺🇸',
            self::NYSE => '🇺🇸',
            self::PKN => '🇺🇸',
            self::NASDAQ_CM => '🇺🇸',
            self::STUTTGART => '🇩🇪',
            self::LONDON => '🇬🇧',
        };
    }
}
