<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotificarEmpleadoEstadoSolicitud extends Notification implements ShouldQueue
{
    use Queueable;

    protected $estado;
    protected $solicitud;

    public function __construct($estado, $solicitud)
    {
        $this->estado = $estado;
        $this->solicitud = $solicitud;
    }

    public function via($notifiable)
    {
        return ['mail']; // Si usas frontend, 'broadcast' también podría servir
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Tu solicitud de vacaciones fue {$this->estado}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu solicitud de vacaciones del {$this->solicitud->fecha_inicio} al {$this->solicitud->fecha_fin} ha sido {$this->estado}.")
            ->line('Gracias por usar el sistema.');
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => "Tu solicitud del {$this->solicitud->fecha_inicio} al {$this->solicitud->fecha_fin} ha sido {$this->estado}.",
            'estado' => $this->estado,
            'solicitud_id' => $this->solicitud->id,
        ];
    }
}
