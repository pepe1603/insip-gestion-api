<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LoginNotification extends Notification
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
            ->subject('Nuevo inicio de sesión detectado')
            ->greeting('Hola ' . $this->user->name)
            ->line('Se ha iniciado sesión en tu cuenta.')
            ->line('**Detalles del acceso:**')
            ->line('IP: ' . $this->ip)
            ->line('Fecha y hora: ' . $this->loginAt->format('Y-m-d H:i:s'))
            ->line('')
            ->line('Si no fuiste tú, cambia tu contraseña inmediatamente.')
            ->action('Cambiar contraseña', env('APP_FRONTEND_RESET_URL', 'http://localhost:3000/reset'))
            ->line('Gracias por usar nuestra plataforma.');
    }
}
