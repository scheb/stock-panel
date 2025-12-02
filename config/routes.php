<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import([
        'path' => '../src/Controller/',
        'namespace' => 'App\Controller',
    ], 'attribute');

    $routingConfigurator->import(Kernel::class, 'attribute');
};
