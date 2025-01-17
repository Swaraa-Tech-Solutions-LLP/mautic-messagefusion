<?php

declare(strict_types=1);

use MauticPlugin\MessageFusionBundle\Mailer\Factory\MessageFusionTransportFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MauticPlugin\\MessageFusionBundle\\', '../')
        ->exclude('../{Config,Mailer/Transport/MessageFusionTransport.php}');

    $services->get(MessageFusionTransportFactory::class)->tag('mailer.transport_factory');
};
