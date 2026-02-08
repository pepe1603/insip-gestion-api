<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LogoutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $logoutAt;

    public function __construct()
    {
        $this->logoutAt = now();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cierre de sesión exitoso')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Has cerrado sesión exitosamente en tu cuenta.')
            ->line('Fecha y hora: ' . $this->logoutAt->format('Y-m-d H:i:s'))
            ->line('Si no fuiste tú, por seguridad cambia tu contraseña lo antes posible.')
            ->action('Cambiar contraseña', env('APP_FRONTEND_RESET_URL', 'http://localhost:3000/reset'))
            ->line('** ¡Gracias por usar nuestra aplicación! **');
    }
}
