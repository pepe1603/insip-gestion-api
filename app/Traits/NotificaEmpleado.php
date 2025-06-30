<?php
namespace App\Traits;

use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as LaravelNotification;

trait NotificaEmpleado
{
    /**
     * Envía una notificación al empleado, usando su usuario si existe,
     * o como fallback, usando su correo electrónico directamente.
     *
     * @param  \App\Models\Empleado  $empleado
     * @param  LaravelNotification  $notificacion
     * @return void
     */
    public function notificarEmpleado($empleado, LaravelNotification $notificacion)
    {
        if ($empleado->user) {
            $empleado->user->notify($notificacion);
        } elseif (!empty($empleado->email)) {
            Notification::route('mail', $empleado->email)->notify($notificacion);
        } else {
            logger()->warning("Empleado sin correo ni usuario para notificar. ID: {$empleado->id}");
        }
    }

}
