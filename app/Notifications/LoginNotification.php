<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $ip;
    protected $loginAt;

    public function __construct($user, $ip)
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->loginAt = now();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Inicio de Sesión Detectado')
            ->greeting('Hola ' . $this->user->name)
            ->line('Se ha iniciado sesión en tu cuenta desde la dirección IP: ' . $this->ip . ' .')
            ->line('Fecha y hora: ' . $this->loginAt->format('Y-m-d H:i:s'))
            ->line('Si no fuiste tú, cambia tu contraseña inmediatamente.')
            ->action('Cambiar contraseña', env('APP_FRONTEND_RESET_URL', 'http://localhost:3000/reset'));
    }
}
