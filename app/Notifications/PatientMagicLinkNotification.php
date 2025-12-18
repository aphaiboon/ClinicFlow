<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientMagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('patient.verify', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Your ClinicFlow Login Link')
            ->greeting('Hello!')
            ->line('Click the button below to securely log in to your patient portal.')
            ->action('Log In', $url)
            ->line('This link will expire in 30 minutes.')
            ->line('If you did not request this login link, please ignore this email.');
    }
}
