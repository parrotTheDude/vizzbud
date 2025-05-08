<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Postmark\Laravel\PostmarkMessage;

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

        return (new PostmarkMessage)
            ->templateId(env('POSTMARK_RESET_TEMPLATE_ID', '39979999'))
            ->templateModel([
                'product_name' => 'VizzBud',
                'action_url' => $resetUrl,
                'support_email' => config('mail.from.address'),
                'year' => now()->year,
                'name' => $notifiable->name ?? 'there',
            ]);
    }
}