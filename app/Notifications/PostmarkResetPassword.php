<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class PostmarkResetPassword extends Notification
{
    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->email,
        ], false));
    
        return (new MailMessage)
            ->mailer('postmark') // <- This is key
            ->withSymfonyMessage(function ($message) use ($resetUrl) {
                $message->getHeaders()->addTextHeader('X-PM-Template', env('POSTMARK_RESET_TEMPLATE_ID'));
                $message->getHeaders()->addTextHeader('X-PM-TemplateModel', json_encode([
                    'action_url' => $resetUrl,
                    'support_email' => config('mail.from.address'),
                    'year' => now()->year,
                ]));
            });
    }
}