<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCodeNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $minutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi MieTemu')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Gunakan kode berikut untuk memverifikasi email Anda:')
            ->line("**{$this->code}**")
            ->line("Kode berlaku selama {$this->minutes} menit. Jangan bagikan kode ini kepada siapa pun.")
            ->line('Jika Anda tidak merasa mendaftar di MieTemu, abaikan email ini.')
            ->salutation('Salam, Tim MieTemu');
    }
}
