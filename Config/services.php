<?php

declare(strict_types=1);

use MauticPlugin\MessagefusionBundle\Mailer\Factory\MessagefusionTransportFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MauticPlugin\\MessagefusionBundle\\', '../')
        ->exclude('../{Config,Helper/MessagefusionResponse.php,Mailer/Transport/MessagefusionTransport.php}');

    $services->get(MessagefusionTransportFactory::class)->tag('mailer.transport_factory');
};
