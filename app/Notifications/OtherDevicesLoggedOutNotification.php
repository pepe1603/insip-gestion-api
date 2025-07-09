<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtherDevicesLoggedOutNotification extends Notification implements ShouldQueue
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
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Sesiones Cerradas en Otros Dispositivos - ' . config('app.name')) // Asunto del correo
                    ->greeting('Hola ' . $notifiable->name . ',') // Saludo personalizado
                    ->line('Te informamos que, a tu solicitud, hemos cerrado la sesión de tu cuenta en todos los demás dispositivos, excepto el que estás utilizando actualmente.') // Primera línea de texto
                    ->line('Esta acción ayuda a proteger tu cuenta si sospechas de actividad no autorizada o simplemente deseas gestionar tus sesiones activas.') // Explicación de la acción
                    ->line('Si no fuiste tú quien solicitó esta acción, por favor, cambia tu contraseña inmediatamente y revisa la actividad reciente de tu cuenta.') // Advertencia de seguridad
                    ->action('Revisar Configuración de Seguridad', url(env('APP_FRONTEND_ACCOUNT_URL'))) // Botón de acción (ajusta la URL según tu aplicación)
                    ->line('Gracias por mantener tu cuenta segura.') // Línea de agradecimiento
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
            'message' => 'Tu sesión ha sido cerrada en todos los demás dispositivos.',
            'action' => 'other_devices_logged_out',
        ];
    }
}
