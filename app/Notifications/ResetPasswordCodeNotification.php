<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCodeNotification extends Notification
{
    use Queueable;
    public string $code; //codigo de verificacion

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {

        $this->code = $code;
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
        return (new MailMessage)
                    ->subject('Tu Código de Restablecimiento de Contraseña')
                    ->line('Has solicitado un restablecimiento de contraseña para tu cuenta.')
                    ->line('Tu código de verificación es: **' . $this->code . '**') // El código en negrita
                    ->line('Este código expirará en ' . config('auth.passwords.users.expire') . ' minutos.')
                    ->line('Por favor, ingresa este código en el formulario de restablecimiento de contraseña de nuestra aplicación.')
                    ->line('Si no solicitaste un restablecimiento de contraseña, ignora este correo electrónico.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'email' => $notifiable->email,
        ];
    }
}
