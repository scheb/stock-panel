<?php

declare(strict_types=1);

namespace App\Entity;

enum Exchange: string
{
    case FRANKFURT = 'FRA';
    case XETRA = 'GER';
    case NASDAQ = 'NMS';
    case NYSE = 'NYQ';

    public function label(): string
    {
        return match($this)
        {
            self::FRANKFURT => 'Frankfurt',
            self::XETRA => 'Xetra',
            self::NASDAQ => 'Nasdaq',
            self::NYSE => 'NYSE',
        };
    }

    public function flag(): string
    {
        return match($this)
        {
            self::FRANKFURT, self::XETRA => '🇩🇪',
            self::NASDAQ, self::NYSE => '🇺🇸',
        };
    }
}
