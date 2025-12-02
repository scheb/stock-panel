<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('sonata_intl', [
        'timezone' => [
            'locales' => [
                'de' => 'Europe/Berlin',
            ],
            'default' => 'Europe/Berlin',
            'detectors' => [
                'sonata.intl.timezone_detector.locale_aware',
            ],
        ],
    ]);
};
