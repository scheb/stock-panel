<?php

declare(strict_types=1);

use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Context\ContextManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('notification_sender', '%env(resolve:NOTIFICATION_SENDER)%');

    $parameters->set('notification_recipient', '%env(resolve:NOTIFICATION_RECIPIENT)%');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\', __DIR__ . '/../src/')
        ->exclude([
        __DIR__ . '/../src/DependencyInjection/',
        __DIR__ . '/../src/Entity/',
        __DIR__ . '/../src/Kernel.php',
        __DIR__ . '/../src/Tests/',
    ]);

    $services->set(ApiClient::class, ApiClient::class)
        ->factory([
        ApiClientFactory::class,
        'createApiClient',
    ])
        ->arg('$retries', 2)
        ->arg('$cache', service('cache.app'));

    $services->set(ContextManagerInterface::class, ApiClient::class)
        ->factory([
        ApiClientFactory::class,
        'createContextManager',
    ])
        ->arg('$retries', 2)
        ->arg('$cache', service('cache.app'));

    $services->set('sonata.intl.timezone_detector.user', 'stdClass');
};
