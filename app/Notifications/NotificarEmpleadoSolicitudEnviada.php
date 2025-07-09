<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Vacaciones; // Importa el modelo de Vacaciones

class NotificarEmpleadoSolicitudEnviada extends Notification implements ShouldQueue
{
    use Queueable;

    public $solicitud; // Para pasar la solicitud a la notificación

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Vacaciones $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $fechaInicio = $this->solicitud->fecha_inicio->format('d/m/Y');
        $fechaFin = $this->solicitud->fecha_fin->format('d/m/Y');
        $diasSolicitados = $this->solicitud->dias_vacaciones_solicitados;

        return (new MailMessage)
                    ->subject('Solicitud de Vacaciones Enviada - Pendiente de Revisión')
                    ->greeting("Hola {$notifiable->nombre_completo},") // Asumiendo que el modelo Empleado tiene 'nombre_completo'
                    ->line('Hemos recibido tu solicitud de vacaciones con los siguientes detalles:')
                    ->line("  • Fechas solicitadas: del {$fechaInicio} al {$fechaFin}")
                    ->line("  • Días solicitados: {$diasSolicitados}")
                    ->line('Tu solicitud ha sido enviada al departamento de Recursos Humanos para su revisión.')
                    ->line('Te notificaremos una vez que tu solicitud sea aprobada o rechazada.')
                    ->action('Ver mi Solicitud', url(env('APP_FRONTEND_LOGIN_URL'))) // Reemplaza con la URL real
                    ->line('¡Gracias!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

