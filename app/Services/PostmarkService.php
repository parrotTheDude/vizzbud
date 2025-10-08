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

    /**
     * Send a Postmark template email.
     *
     * @param int|string $templateId
     * @param string     $to
     * @param array      $variables
     * @param string|null $from
     * @param string|null $tag
     * @param array       $options  [
     *    'replyTo' => string,
     *    'cc' => string,
     *    'bcc' => string,
     *    'headers' => array<array{Name:string, Value:string}>,
     *    'attachments' => array<array{Name:string, Content:string, ContentType:string, ContentID?:string}>,
     *    'trackLinks' => 'None'|'HtmlAndText'|'HtmlOnly'|'TextOnly',
     *    'metadata' => array<string,string>,
     * ]
     * @param string|null $stream
     */
    public function sendEmail(
        $templateId,
        string $to,
        array $variables = [],
        ?string $from = null,
        ?string $tag = null,
        array $options = [],
        ?string $stream = null
    ) {
        return $this->client->sendEmailWithTemplate(
            $from ?? config('services.postmark.from_email'),
            $to,
            (int) $templateId,
            $variables,
            true,                               // inlineCss
            $tag,                               // tag
            true,                               // trackOpens
            $options['replyTo']     ?? null,    // replyTo
            $options['cc']          ?? null,    // cc
            $options['bcc']         ?? null,    // bcc
            $options['headers']     ?? null,    // headers (array of Name/Value)
            $options['attachments'] ?? null,    // attachments (array)
            $options['trackLinks']  ?? 'None',  // trackLinks
            $options['metadata']    ?? null,    // metadata (array)
            $stream ?? config('services.postmark.message_stream', 'outbound')
        );
    }
}