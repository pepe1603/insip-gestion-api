<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmployeeAccountCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected string $loginUrl;
    protected string $rawPassword; // Contraseña sin hashear (temporalmente para la notificación)
    // protected string $ipAddress; // Considera si realmente necesitas esto en el correo al usuario
    // protected \DateTimeInterface $createdAt; // Ya está disponible en $this->user->created_at

    /**
     * Crea una nueva instancia de notificación.
     *
     * @param User $user El objeto de usuario creado.
     * @param string $loginUrl La URL para iniciar sesión en la plataforma.
     * @param string $rawPassword La contraseña generada por el administrador (sin hashear).
     * @param string|null $ipAddress La dirección IP desde donde se realizó el registro (opcional para el constructor).
     */
    public function __construct( $user, string $loginUrl, string $rawPassword, ?string $ipAddress = null) // Hacemos IP opcional aquí
    {
        $this->user = $user;
        $this->loginUrl = $loginUrl;
        $this->rawPassword = $rawPassword;
        $this->ipAddress = $ipAddress ?? 'Desconocida'; // Asigna un valor por defecto si es nulo
    }

    /**
     * Obtiene los canales de notificación.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtiene la representación de la notificación por correo electrónico.
     *
     * @param  object  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Obtener la URL para restablecer la contraseña
        // Es recomendable pasarla también por el constructor si es dinámica o desde una configuración específica
        $resetPasswordUrl = env('APP_FRONTEND_RESET_URL', 'http://localhost:3000/auth/reset-password');

        // Puedes usar el created_at del modelo User directamente
        $createdAtFormatted = $this->user->created_at ? $this->user->created_at->format('d/m/Y H:i:s') : 'Fecha no disponible';
        $userRoleName = ucfirst($this->user->role->value); // Para que se vea bonito (Admin, Supervisor, Employee, etc.)

        return (new MailMessage)
                    ->subject('¡Bienvenido/a a ' . config('app.name') . '! Su Cuenta ha Sido Creada')
                    ->greeting('Hola ' . $notifiable->name . '!')
                    ->line('Su cuenta ha sido creada exitosamente en la plataforma **' . config('app.name') . '**.')
                    ->line('A continuación, sus credenciales temporales:')
                    ->line('**Correo Electrónico:** ' . $this->user->email)
                    ->line('**Contraseña Temporal:** `' . $this->rawPassword . '`')
                    ->line('**Rol Asignado:** ' . $userRoleName)
                    ->action('Acceder a la Plataforma', $this->loginUrl) // Botón principal para iniciar sesión
                    ->line('Por seguridad, le pedimos que cambie su contraseña inmediatamente después de iniciar sesión por primera vez.')
                    ->action('Cambiar Contraseña Ahora', $resetPasswordUrl) // Botón para ir directamente a cambiar contraseña
                    ->line('Si tiene alguna pregunta, no dude en contactarnos.')
                    ->salutation('Atentamente, el equipo de ' . config('app.name'));
    }

    /**
     * Obtiene la representación de la notificación por base de datos.
     *
     * @param  object  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'user_role' => $this->user->role,
            'login_url' => $this->loginUrl,
            'ip_address' => $this->ipAddress,
            'created_at' => $this->user->created_at ? $this->user->created_at->toDateTimeString() : null, // Usar el created_at del usuario
            'message' => 'Cuenta de usuario creada. Contraseña temporal y cambio de contraseña requerido.',
        ];
    }
}
