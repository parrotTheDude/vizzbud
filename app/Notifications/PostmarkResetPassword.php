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
            ->mailer('postmark') // Uses your custom mailer
            ->subject('Reset Your Password')
            ->view('emails.reset-password', [ // Optional: only if not using Postmark template
                'resetUrl' => $resetUrl,
            ])
            ->withSymfonyMessage(function ($message) use ($resetUrl) {
                $message->getHeaders()->addTextHeader('X-PM-Template', config('services.postmark.reset_password_template_id'));
                $message->getHeaders()->addTextHeader('X-PM-TemplateModel', json_encode([
                    'action_url' => $resetUrl,
                    'support_email' => config('mail.from.address'),
                    'year' => now()->year,
                ]));
            });
    }
}