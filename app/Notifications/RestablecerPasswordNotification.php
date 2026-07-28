<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestablecerPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $url = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->correo);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña - RCH Hospital')
            ->greeting('Hola, ' . $notifiable->nombre)
            ->line('Has recibido este correo porque se solicitó un enlace para restablecer la contraseña de tu cuenta de acceso al Sistema de Control de Mantenimiento de RCH Hospital.')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace de recuperación es de un solo uso y expirará en 60 minutos.')
            ->line('Si no solicitaste este cambio, por favor ignora este correo. Tu contraseña actual no sufrirá ninguna modificación.');
    }
}
