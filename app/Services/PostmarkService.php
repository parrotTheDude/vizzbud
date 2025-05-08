<?php

namespace App\Services;

use Postmark\PostmarkClient;

class PostmarkService
{
    protected PostmarkClient $client;

    public function __construct()
    {
        $this->client = new PostmarkClient(config('services.postmark.token'));
    }

    public function sendEmail($templateId, $to, $variables = [], $from = null, $alias = null, $stream = null)
    {
        return $this->client->sendEmailWithTemplate(
            $from ?? config('services.postmark.from_email'),
            $to,
            (int) $templateId,
            $variables,
            true,                             // Track opens
            $alias ?? 'Vizzbud',              // Alias
            true,                             // Inline CSS
            null, null, null, null, null,
            'None',
            null,
            $stream ?? config('services.postmark.message_stream', 'outbound')
        );
    }
}