<?php

declare(strict_types=1);

namespace MauticPlugin\MessageFusionBundle\Mailer\Factory;

use Mautic\EmailBundle\Model\TransportCallback;
use MauticPlugin\MessageFusionBundle\Mailer\Transport\MessageFusionTransport;
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

class MessageFusionTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private TransportCallback   $transportCallback,
        private TranslatorInterface $translator,
        EventDispatcherInterface    $eventDispatcher,
        HttpClientInterface         $client = null,
        LoggerInterface             $logger = null
    )
    {
          // Initialize the logger
          $this->Newlogger = new Logger('MessagefusionTransportFactory');
        //   $this->Newlogger->pushHandler(new StreamHandler('var/logs/messagefusion/messagefusion_factory.log', Logger::DEBUG));
        //   $this->Newlogger->debug('MessagefusionTransportFactory is working ');

        parent::__construct($eventDispatcher, $client, $logger);
    }

    /**
     * @return string[]
     */
    protected function getSupportedSchemes(): array
    {
        return [MessageFusionTransport::MAUTIC_MESSAGEFUSION_API_SCHEME];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if (MessageFusionTransport::MAUTIC_MESSAGEFUSION_API_SCHEME === $dsn->getScheme()) {
            $host = $dsn->getHost();
            $port = $dsn->getPort();
            return new MessageFusionTransport(
                $this->getPassword($dsn),
                $host,
                $this->transportCallback,
                $this->client,
                $this->dispatcher,
                $this->logger
            );
        }

        throw new UnsupportedSchemeException($dsn, 'messagefusion', $this->getSupportedSchemes());
    }
}
