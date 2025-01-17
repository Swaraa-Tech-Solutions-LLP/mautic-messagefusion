<?php

declare(strict_types=1);

namespace MauticPlugin\MessagefusionBundle\Mailer\Transport;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportTrait;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MessagefusionTransport extends AbstractApiTransport implements TokenTransportInterface
{
    private $Newlogger;

    use TokenTransportTrait;

    public const MAUTIC_MESSAGEFUSION_API_SCHEME = 'mf+api';

    public const MESSAGEFUSION_HOSTS = 'messagefusion.org';

    public function __construct(
        private string $apiKey,
        string $region,
        private TransportCallback $callback,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($client, $dispatcher, $logger);
        
        // Initialize the logger
        $this->Newlogger = new Logger('MessagefusionTransport');
        $this->Newlogger->pushHandler(new StreamHandler('var/logs/messagefusion/messagefusion_transport.log', Logger::DEBUG));
        $this->Newlogger->debug('MessagefusionTransport is working ');

        $this->host = self::MESSAGEFUSION_HOSTS;
    }

    public function __toString(): string
    {
        return sprintf(self::MAUTIC_MESSAGEFUSION_API_SCHEME.'://%s', $this->host);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $payload = $this->getPayload($email, $envelope);
        $this->Newlogger->debug('MessagefusionTransport is $payload ',['payload' => $payload]);
        // $this->Newlogger->debug('MessagefusionTransport APi Url ---- https://'.$this->host.'/api/v1/send/message');

        $response = $this->client->request('POST', 'https://'.$this->host.'/api/v1/send/message', [
            'json' => $payload,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Server-API-Key' => $this->apiKey,
            ],
        ]);
        $this->Newlogger->debug('MessagefusionTransport is $response ',['res' => $response]);
        
        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new HttpTransportException('Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote Messagefusion server.', $response, 0, $e);
        }
        $this->Newlogger->debug('MessagefusionTransport is $statusCode ',['statusCode' => $statusCode]);
    
        if (200 !== $statusCode) {
            throw new HttpTransportException('Unable to send an email: '.$result['message'].sprintf(' (code %d).', $statusCode), $response);
        }
    
        // Assuming the response contains the 'message_id' field
        $this->Newlogger->debug('MessagefusionTransport is $result ',['result' => $result]);

        // Check if 'message_id' exists before setting it
        if (isset($result['data']['message_id']) && !empty($result['data']['message_id'])) {
            $this->Newlogger->debug('MessagefusionTransport is $result ',['result' => $result]);
            $sentMessage->setMessageId($result['data']['message_id']);
        } else {
            // Log a warning if message_id is missing
            $this->Newlogger->warning('Messagefusion API did not return a message_id.');
            throw new HttpTransportException('Messagefusion API did not return a message_id.');
        }
    
        return $response;
    }

    /**
     * @return array<mixed>
     */
    private function getPayload(Email $email, Envelope $envelope): array
    {   

        $payload = [
            'from' => $envelope->getSender()->getAddress(),
            'to' => array_map(fn (Address $address) => $address->getAddress(), $this->getRecipients($email, $envelope)),
            'subject' => $email->getSubject(),
        ];
        if ($emails = $email->getCc()) {
            $payload['cc'] = array_map(fn (Address $address) => $address->getAddress(), $emails);
        }
        if ($emails = $email->getBcc()) {
            $payload['bcc'] = array_map(fn (Address $address) => $address->getAddress(), $emails);
        }
        if ($email->getTextBody()) {
            $payload['plain_body'] = $email->getTextBody();
        }
        if ($email->getHtmlBody()) {
            $payload['html_body'] = $email->getHtmlBody();
        }
        if ($attachments = $this->prepareAttachments($email)) {
            $payload['attachments'] = $attachments;
        }
        if ($headers = $this->getCustomHeaders($email)) {
            $payload['headers'] = $headers;
        }
        if ($emails = $email->getReplyTo()) {
            $payload['reply_to'] = $emails[0]->getAddress();
        }

        // $payload['campaign_id'] = $this->getCampaignId($metadata, $metadataSet);

        return $payload;
    }

    private function prepareAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'name' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'data' => base64_encode($attachment->getBody()),
            ];
        }

        return $attachments;
    }

    private function getCustomHeaders(Email $email): array
    {
        $headers = [];
        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type', 'sender', 'reply-to'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[] = [
                'key' => $header->getName(),
                'value' => $header->getBodyAsString(),
            ];
        }

        return $headers;
    }


    /**
     * @param array<mixed> $metadata
     * @param array<mixed> $metadataSet
     */
    // private function getCampaignId(array $metadata, array $metadataSet): string
    // {
    //     $campaignId = '';

    //     if (!empty($metadata)) {
    //         $id = '';

    //         if (!empty($metadataSet['utmTags']['utmCampaign'])) {
    //             $id = $metadataSet['utmTags']['utmCampaign'];
    //         } elseif (!empty($metadataSet['emailId']) && !empty($metadataSet['emailName'])) {
    //             $id = $metadataSet['emailId'].':'.$metadataSet['emailName'];
    //         } elseif (!empty($metadataSet['emailId'])) {
    //             $id = $metadataSet['emailId'];
    //         }

    //         $campaignId = mb_strcut($id, 0, 64);
    //     }

    //     return $campaignId;
    // }


    public function getMaxBatchLimit(): int
    {
        return 5000;
    }
}
