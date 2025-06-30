<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotificarAdminSolicitudVacaciones extends Notification implements ShouldQueue
{
    use Queueable;

    public $empleado;

    public function __construct($empleado)
    {
        $this->empleado = $empleado;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nueva solicitud de vacaciones')
            ->greeting('Hola Elizabeth')
            ->line("El empleado {$this->empleado->nombre} ha solicitado vacaciones.")
            ->action('Revisar en el sistema', url('/admin/vacaciones/pendientes'));
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => "El empleado {$this->empleado->nombre} solicitó vacaciones.",
            'url' => '/admin/vacaciones/pendientes'
        ];
    }
}
