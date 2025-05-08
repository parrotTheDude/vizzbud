<?php

namespace App\Services;

use GuzzleHttp\Client;

class PostmarkService
{
    protected string $apiKey;
    protected Client $client;
    protected string $endpoint = 'https://api.postmarkapp.com/email/withTemplate';

    public function __construct()
    {
        $this->apiKey = config('services.postmark.token');
        $this->client = new Client([
            'headers' => [
                'X-Postmark-Server-Token' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function sendTemplate(string $to, string $templateId, array $templateModel, string $from = null, string $messageStream = 'outbound')
    {
        $from = $from ?? config('mail.from.address');

        $response = $this->client->post($this->endpoint, [
            'json' => [
                'From' => $from,
                'To' => $to,
                'TemplateId' => $templateId,
                'TemplateModel' => $templateModel,
                'MessageStream' => $messageStream,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}