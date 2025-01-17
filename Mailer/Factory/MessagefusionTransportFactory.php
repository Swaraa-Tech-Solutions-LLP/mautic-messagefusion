<?php

declare(strict_types=1);

namespace MauticPlugin\MessagefusionBundle\Mailer\Factory;

use Mautic\EmailBundle\Model\TransportCallback;
use MauticPlugin\MessagefusionBundle\Mailer\Transport\MessagefusionTransport;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MessagefusionTransportFactory extends AbstractTransportFactory
{
    private $Newlogger;

    public function __construct(
        private TransportCallback $transportCallback,
        private TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        HttpClientInterface $client = null,
        LoggerInterface $logger = null
    ) {

        // Initialize the logger
        $this->Newlogger = new Logger('MessagefusionTransportFactory');
        $this->Newlogger->pushHandler(new StreamHandler('var/logs/messagefusion/messagefusion_factory.log', Logger::DEBUG));
        $this->Newlogger->debug('MessagefusionTransportFactory is working ');

        parent::__construct($eventDispatcher, $client, $logger);
    }

    /**
     * @return string[]
     */
    protected function getSupportedSchemes(): array
    {
        return [MessagefusionTransport::MAUTIC_MESSAGEFUSION_API_SCHEME];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if (MessagefusionTransport::MAUTIC_MESSAGEFUSION_API_SCHEME === $dsn->getScheme()) {
            // Removed region validation and no default region set
            $region = (string) $dsn->getOption('region', '');

            return new MessagefusionTransport(
                $this->getPassword($dsn),
                $region, // Will pass null if region is not provided
                $this->transportCallback,
                $this->client,
                $this->dispatcher,
                $this->logger
            );
        }

        throw new UnsupportedSchemeException($dsn, 'messagefusion', $this->getSupportedSchemes());
    }
}
