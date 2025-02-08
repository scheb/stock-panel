<?php

declare(strict_types=1);

namespace App\Entity;

enum Exchange: string
{
    // German
    case FRA = 'FRA';
    case GER = 'GER';
    case BER = 'BER';
    case DUS = 'DUS';
    case HAM = 'HAM';
    case MUN = 'MUN';
    case STU = 'STU';

    // US
    case NMS = 'NMS';
    case NYQ = 'NYQ';
    case PKN = 'PKN';
    case NCM = 'NCM';

    // European
    case LSE = 'LSE';
    case AQS = 'AQS';
    case IOB = 'IOB';
    case VIE = 'VIE';
    case BRU = 'BRU';
    case PRA = 'PRA';
    case CPH = 'CPH';
    case HEL = 'HEL';
    case PAR = 'PAR';
    case ATH = 'ATH';
    case BUD = 'BUD';
    case AMS = 'AMS';
    case OSL = 'OSL';
    case WSE = 'WSE';
    case LIS = 'LIS';
    case STO = 'STO';

    // Asia
    case HKG = 'HKG';

    public function label(): string
    {
        return match($this) {
            self::FRA => 'Frankfurt',
            self::GER => 'Xetra',
            self::BER => 'Berlin',
            self::DUS => 'Düsseldorf',
            self::HAM => 'Hamburg',
            self::MUN => 'München',
            self::STU => 'Stuttgart',

            self::NMS => 'Nasdaq',
            self::NYQ => 'NYSE',
            self::PKN => 'OTC Markets OTCPK',
            self::NCM => 'NasdaqCM',

            self::LSE => 'London',
            self::VIE => 'Wien',
            self::BRU => 'Brüssel',
            self::PRA => 'Prag',
            self::CPH => 'Copenhagen',
            self::HEL => 'Helsinki',
            self::PAR => 'Paris',
            self::ATH => 'Athen',
            self::BUD => 'Budapest',
            self::AMS => 'Amsterdam',
            self::OSL => 'Oslo',
            self::WSE => 'Warschau',
            self::LIS => 'Lisabon',
            self::STO => 'Stockholm',

            self::HKG => 'Hong Kong',
        };
    }

    public function flag(): string
    {
        return match($this) {
            self::FRA => '🇩🇪',
            self::GER => '🇩🇪',
            self::BER => '🇩🇪',
            self::DUS => '🇩🇪',
            self::HAM => '🇩🇪',
            self::MUN => '🇩🇪',
            self::STU => '🇩🇪',

            self::NMS => '🇺🇸',
            self::NYQ => '🇺🇸',
            self::PKN => '🇺🇸',
            self::NCM => '🇺🇸',

            self::LSE => '🇬🇧',
            self::VIE => '🇦🇹',
            self::BRU => '🇧🇪',
            self::PRA => '🇨🇿',
            self::CPH => '🇩🇰',
            self::HEL => '🇫🇮',
            self::PAR => '🇫🇷',
            self::ATH => '🇬🇷',
            self::BUD => '🇭🇺',
            self::AMS => '🇳🇱',
            self::OSL => '🇳🇴',
            self::WSE => '🇵🇱',
            self::LIS => '🇵🇹',
            self::STO => '🇸🇪',

            self::HKG => '🇭🇰',
        };
    }
}
