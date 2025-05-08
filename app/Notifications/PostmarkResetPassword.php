<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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
    
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->mailer('postmark') 
            ->subject('Reset Your Password')
            ->withSymfonyMessage(function ($message) use ($resetUrl) {
                $message->getHeaders()->addTextHeader('X-PM-Template', '39979999');
                $message->getHeaders()->addTextHeader('X-PM-TemplateModel', json_encode([
                    'action_url' => $resetUrl,
                    'support_email' => config('mail.from.address'),
                    'year' => now()->year,
                ]));
            });
    }
}