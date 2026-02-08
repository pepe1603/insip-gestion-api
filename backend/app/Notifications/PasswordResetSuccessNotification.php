<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
    public function toMail($notifiable)
        {
            return (new MailMessage)
                        ->subject('Contraseña Restablecida Exitosamente - ' . config('app.name')) // Asunto del correo
                        ->greeting('Hola ' . $notifiable->name . ',') // Saludo personalizado
                        ->line('Te confirmamos que la contraseña de tu cuenta en ' . config('app.name') . ' ha sido restablecida exitosamente.') // Primera línea de texto
                        ->line('Si no fuiste tú quien realizó este cambio, por favor, contacta con nuestro equipo de soporte inmediatamente para asegurar la seguridad de tu cuenta.') // Advertencia de seguridad
                        ->action('Acceder a tu cuenta', url(env('APP_FRONTEND_LOGIN_URL'))) // Botón de acción para iniciar sesión
                        ->line('Gracias por confiar en nosotros.') // Línea de agradecimiento
                        ->salutation('Saludos cordiales,') // Despedida
                        ->line(config('app.name') . ' Equipo'); // Firma del equipo
        }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Tu contraseña ha sido restablecida exitosamente.',
            'action' => 'password_reset_success',
        ];
    }
}
