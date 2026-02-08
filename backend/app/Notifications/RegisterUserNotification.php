<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RegisterUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $loginUrl;
    protected $registerAt;

    public function __construct($user, string $loginUrl, $ip)
    {
        $this->user = $user;
        $this->loginUrl = $loginUrl;
        $this->ip = $ip;
        $this->registerAt = now();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a la Plataforma!')
            ->greeting('Hola ' . $this->user->name . ' 👋') // Corregido a $this->user->name
            ->line('Tu cuenta ha sido creada exitosamente.') // Separado en dos líneas para claridad
            ->line('Fecha y hora: ' . $this->registerAt->format('Y-m-d H:i:s'))
            ->line('**Detalles de tu cuenta:**')
            ->line('Email: ' . $this->user->email)
            ->line('Rol: ' . ucfirst($this->user->role))
            ->line('IP: ' . $this->ip)
            ->action('Iniciar sesión ahora', $this->loginUrl)
            ->line('')
            ->line('Si no fuiste tú, cambia tu contraseña inmediatamente.')
            ->action('Cambiar contraseña', env('APP_FRONTEND_RESET_URL', 'http://localhost:3000/reset-password')) // Recomiendo usar reset-password como en tu .env
            ->line('Gracias por unirte a nosotros. ¡Esperamos que tengas una excelente experiencia!');
    }
}
